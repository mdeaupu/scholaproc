<?php

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('can calculate estimated subtotal correctly', function () {
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
        'is_taxable' => true,
        'ppn_rate' => 11,
        'pph_22_rate' => 1.5,
        'pph_23_rate' => 2,
    ]);

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'quantity' => 2,
        'estimated_price' => 50000,
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'quantity' => 1,
        'estimated_price' => 150000,
    ]);

    expect($procurement->estimatedSubtotal())->toEqual(250000);
});

test('can calculate tax and grand total correctly', function () {
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
        'is_taxable' => true,
        'ppn_rate' => 11,
        'pph_22_rate' => 1.5,
        'pph_23_rate' => 2,
    ]);

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'quantity' => 1,
        'estimated_price' => 100000,
        'official_price' => null,
        'is_pph' => true,
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'quantity' => 1,
        'estimated_price' => 100000,
        'official_price' => null,
        'is_pph' => false,
    ]);

    expect($procurement->estimatedSubtotal())->toEqual(200000)
        ->and($procurement->totalPpn())->toEqual(22000)
        ->and($procurement->totalPph22())->toEqual(1500)
        ->and($procurement->totalPph23())->toEqual(2000)
        ->and($procurement->grandTotal())->toEqual(222000)
        ->and($procurement->netTotal())->toEqual(218500);
});

test('throws exception when submitting without items', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $procurement = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);

    expect(fn () => $procurement->submit($user))
        ->toThrow(Exception::class, 'Pengajuan tidak dapat di-submit. Pastikan status draft atau perlu revisi dan memiliki minimal 1 item.');
});

test('can transition state from draft to submitted and record history', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $procurement = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);
    ProcurementRequestItem::factory()->create(['procurement_request_id' => $procurement->id]);

    $procurement->submit($user);

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_SUBMITTED);

    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $procurement->id,
        'status' => ProcurementRequest::STATUS_SUBMITTED,
        'user_id' => $user->id,
    ]);
});

test('can transition workflow through negotiation and completion', function () {
    $school = School::factory()->create();
    $schoolUser = User::factory()->school()->create(['school_id' => $school->id]);
    $admin = User::factory()->admin()->create();
    $procurement = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);
    ProcurementRequestItem::factory()->create(['procurement_request_id' => $procurement->id]);

    $procurement->submit($schoolUser);
    expect($procurement->status)->toBe(ProcurementRequest::STATUS_SUBMITTED);

    $procurement->verify($admin);
    expect($procurement->status)->toBe(ProcurementRequest::STATUS_VERIFIED);

    $procurement->startNegotiation($admin);
    expect($procurement->status)->toBe(ProcurementRequest::STATUS_NEGOTIATION);

    $procurement->update([
        'status' => ProcurementRequest::STATUS_PAYMENT_PENDING,
        'verified_at' => now(),
        'verified_by' => $admin->id,
    ]);

    $procurement->confirmPayment($admin);
    expect($procurement->payment_confirmed_at)->not->toBeNull()
        ->and($procurement->payment_confirmed_by)->toBe($admin->id);

    $procurement->complete($admin);
    expect($procurement->status)->toBe(ProcurementRequest::STATUS_COMPLETED);
});

test('cannot complete before receipt and payment are confirmed', function () {
    $admin = User::factory()->admin()->create();
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_PAYMENT_PENDING,
    ]);

    expect(fn () => $procurement->complete($admin))
        ->toThrow(Exception::class, 'Pengajuan hanya dapat diselesaikan setelah penerimaan barang dan pembayaran dikonfirmasi pada status menunggu pembayaran.');
});

test('reject records reason in history', function () {
    $admin = User::factory()->admin()->create();
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_SUBMITTED,
    ]);

    $procurement->reject($admin, 'Anggaran melebihi batas yang ditentukan');

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_REJECTED);

    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $procurement->id,
        'status' => ProcurementRequest::STATUS_REJECTED,
        'notes' => 'Ditolak: Anggaran melebihi batas yang ditentukan',
        'user_id' => $admin->id,
    ]);
});

test('school can cancel an own request and reason is recorded', function () {
    $school = School::factory()->create();
    $schoolUser = User::factory()->school()->create(['school_id' => $school->id]);
    $procurement = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_SUBMITTED,
    ]);

    $procurement->cancel($schoolUser, 'Anggaran dialihkan ke kebutuhan lain');

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_CANCELLED);

    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $procurement->id,
        'status' => ProcurementRequest::STATUS_CANCELLED,
        'notes' => 'Dibatalkan: Anggaran dialihkan ke kebutuhan lain',
        'user_id' => $schoolUser->id,
    ]);
});

test('school cannot verify a request', function () {
    $school = School::factory()->create();
    $schoolUser = User::factory()->school()->create(['school_id' => $school->id]);
    $procurement = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_SUBMITTED,
    ]);

    expect(fn () => $procurement->verify($schoolUser))
        ->toThrow(AuthorizationException::class);

    expect($procurement->fresh()->status)->toBe(ProcurementRequest::STATUS_SUBMITTED);
});

test('admin cannot submit a school request', function () {
    $admin = User::factory()->admin()->create();
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);
    ProcurementRequestItem::factory()->create(['procurement_request_id' => $procurement->id]);

    expect(fn () => $procurement->submit($admin))
        ->toThrow(AuthorizationException::class);

    expect($procurement->fresh()->status)->toBe(ProcurementRequest::STATUS_DRAFT);
});

test('draft item can store an empty specification as null', function () {
    $procurement = ProcurementRequest::factory()->create();

    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'specification' => '',
    ]);

    expect($item->fresh()->specification)->toBeNull();
});

test('submit rejects an item with an empty specification', function () {
    $school = School::factory()->create();
    $schoolUser = User::factory()->school()->create(['school_id' => $school->id]);
    $procurement = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'specification' => null,
    ]);

    expect(fn () => $procurement->submit($schoolUser))
        ->toThrow(Exception::class, 'Spesifikasi barang wajib diisi dan tidak boleh menggunakan placeholder "-".');
});

test('uses official subtotal when official price is set', function () {
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'quantity' => 2,
        'estimated_price' => 50000,
        'official_price' => 60000,
    ]);

    expect($procurement->estimatedSubtotal())->toEqual(100000)
        ->and($procurement->officialSubtotal())->toEqual(120000);

    expect($procurement->grandTotal())->toEqual(133200);
});

test('returns zero ppn when not taxable', function () {
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
        'is_taxable' => false,
    ]);

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'quantity' => 1,
        'estimated_price' => 500000,
        'official_price' => null,
    ]);

    expect($procurement->totalPpn())->toEqual(0)
        ->and($procurement->grandTotal())->toEqual(500000);
});

test('canSubmit returns false when no items', function () {
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);

    expect($procurement->canSubmit())->toBeFalse();

    ProcurementRequestItem::factory()->create(['procurement_request_id' => $procurement->id]);
    expect($procurement->canSubmit())->toBeTrue();
});

test('throws exception when verifying non-submitted request', function () {
    $admin = User::factory()->admin()->create();
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);

    expect(fn () => $procurement->verify($admin))
        ->toThrow(Exception::class, 'Hanya pengajuan berstatus submitted yang dapat diverifikasi.');
});
