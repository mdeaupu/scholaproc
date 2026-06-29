<?php

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\Supplier;
use App\Models\User;
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
    $user = User::factory()->create();
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);

    expect(fn() => $procurement->submit($user))
        ->toThrow(Exception::class, 'Pengajuan tidak dapat di-submit. Pastikan status draft dan memiliki minimal 1 item.');
});

test('can transition state from draft to submitted and record history', function () {
    $user = User::factory()->create();
    $procurement = ProcurementRequest::factory()->create([
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

test('can transition workflow correctly up to completion', function () {
    $user = User::factory()->create();
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);
    ProcurementRequestItem::factory()->create(['procurement_request_id' => $procurement->id]);
    $supplier = Supplier::factory()->create();

    $procurement->submit($user);

    $procurement->verify($user);
    expect($procurement->status)->toBe(ProcurementRequest::STATUS_VERIFIED);

    $procurement->assignSupplier($supplier, $user);
    expect($procurement->status)->toBe(ProcurementRequest::STATUS_SUPPLIER_ASSIGNED)
        ->and($procurement->supplier_id)->toBe($supplier->id);

    $procurement->markItemsPrepared($user);
    expect($procurement->status)->toBe(ProcurementRequest::STATUS_ITEMS_PREPARED);

    $procurement->complete($user);
    expect($procurement->status)->toBe(ProcurementRequest::STATUS_COMPLETED);
});

test('reject records reason in history', function () {
    $user = User::factory()->create();
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_SUBMITTED,
    ]);

    $procurement->reject($user, 'Anggaran melebihi batas yang ditentukan');

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_REJECTED);

    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $procurement->id,
        'status' => ProcurementRequest::STATUS_REJECTED,
        'notes' => 'Ditolak: Anggaran melebihi batas yang ditentukan',
        'user_id' => $user->id,
    ]);
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
    $user = User::factory()->create();
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);

    expect(fn() => $procurement->verify($user))
        ->toThrow(Exception::class, 'Hanya pengajuan berstatus submitted yang dapat diverifikasi.');
});