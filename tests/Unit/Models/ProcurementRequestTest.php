<?php

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Models\Subsidiary;
use App\Models\TaxRule;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function configureProcurementTaxes(ProcurementRequest $request, User $actor, array $rates): void
{
    $subsidiary = Subsidiary::factory()->create();
    $request->forceFill(['status' => ProcurementRequest::STATUS_VERIFIED])->save();
    $request->assignSubsidiary($subsidiary, $actor);

    $approver = User::factory()->admin()->create();
    $verifier = User::factory()->admin()->create();
    foreach ($rates as $taxType => $rate) {
        TaxRule::create([
            'tax_type' => $taxType,
            'name' => strtoupper($taxType).' test',
            'version' => 1,
            'status' => TaxRule::STATUS_ACTIVE,
            'subsidiary_id' => $subsidiary->id,
            'rate' => $rate,
            'calculation_method' => 'percentage',
            'legal_basis' => 'Dasar hukum untuk pengujian.',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);
    }

    $request->evaluateTaxConfiguration($actor);
}

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
    $admin = User::factory()->admin()->create();
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
        'is_taxable' => true,
        'ppn_rate' => null,
        'pph_22_rate' => null,
        'pph_23_rate' => null,
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
    configureProcurementTaxes($procurement, $admin, [
        TaxRule::TAX_PPN => 7.25,
        TaxRule::TAX_PPH22 => 1.5,
        TaxRule::TAX_PPH23 => 2,
    ]);

    expect($procurement->estimatedSubtotal())->toEqual(200000)
        ->and($procurement->totalPpn())->toEqual(14500)
        ->and($procurement->totalPph22())->toEqual(1500)
        ->and($procurement->totalPph23())->toEqual(2000)
        ->and($procurement->grandTotal())->toEqual(214500)
        ->and($procurement->netTotal())->toEqual(211000);
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

test('negotiation requires a subsidiary assignment', function () {
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

    expect(fn () => $procurement->startNegotiation($admin))
        ->toThrow(\LogicException::class, 'Tetapkan Anak Perusahaan aktif sebelum memulai negosiasi.');

    $procurement->assignSubsidiary(Subsidiary::factory()->create(), $admin);
    $procurement->startNegotiation($admin);
    expect($procurement->status)->toBe(ProcurementRequest::STATUS_NEGOTIATION);
});

test('cannot complete before receipt and payment are confirmed', function () {
    $admin = User::factory()->admin()->create();
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_PAYMENT_PENDING,
    ]);

    expect(fn () => $procurement->complete($admin))
        ->toThrow(Exception::class, 'Pengadaan belum dapat diselesaikan: penerimaan penuh, administrasi, pembayaran terkonfirmasi, atau tindak lanjut masih belum terpenuhi.');
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
    $admin = User::factory()->admin()->create();
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
        'is_taxable' => true,
        'ppn_rate' => null,
    ]);

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'quantity' => 2,
        'estimated_price' => 50000,
        'official_price' => 60000,
    ]);

    expect($procurement->estimatedSubtotal())->toEqual(100000)
        ->and($procurement->officialSubtotal())->toEqual(120000);

    configureProcurementTaxes($procurement, $admin, [
        TaxRule::TAX_PPN => 5,
        TaxRule::TAX_PPH22 => 0,
        TaxRule::TAX_PPH23 => 0,
    ]);
    expect($procurement->grandTotal())->toEqual(126000);
});

test('does not present a final tax total when no validated rules are available', function () {
    $admin = User::factory()->admin()->create();
    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'quantity' => 1,
        'estimated_price' => 500000,
        'official_price' => null,
    ]);
    $result = $procurement->evaluateTaxConfiguration($admin);

    expect($result['status'])->toBe('NEEDS_REVIEW')
        ->and($procurement->isTaxConfigurationValidated())->toBeFalse()
        ->and(fn () => $procurement->totalPpn())->toThrow(\LogicException::class);
});

test('manual tax fields cannot be mass assigned on procurement requests', function () {
    $procurement = new ProcurementRequest([
        'is_taxable' => true,
        'ppn_rate' => 11,
        'tax_configuration_validated' => true,
    ]);

    expect($procurement->getAttributes())->not->toHaveKeys([
        'is_taxable',
        'ppn_rate',
        'tax_configuration_validated',
    ])
        ->and(method_exists($procurement, 'configureTax'))->toBeFalse();
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
test('hasSubsidiary mengembalikan nilai boolean yang tepat', function () {
    $procurement = ProcurementRequest::factory()->create([
        'subsidiary_id' => null,
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    expect($procurement->hasSubsidiary())->toBeFalse();

    $subsidiary = Subsidiary::factory()->create();
    $procurement->assignSubsidiary($subsidiary, User::factory()->admin()->create());

    expect($procurement->refresh()->hasSubsidiary())->toBeTrue();
});

test('hasOfficialPrices mengecek apakah semua item memiliki harga resmi', function () {
    $procurement = ProcurementRequest::factory()->create();

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'official_price' => null,
    ]);
    expect($procurement->hasOfficialPrices())->toBeFalse();

    $procurement->items()->update(['official_price' => 150000]);
    expect($procurement->refresh()->hasOfficialPrices())->toBeTrue();
});

test('hasSignatories mengecek ketersediaan penandatangan', function () {
    $procurement = ProcurementRequest::factory()->create();
    expect($procurement->hasSignatories())->toBeFalse();

    $procurement->signatories()->create(['name' => 'Kepsek', 'role' => 'headmaster']);
    $procurement->signatories()->create(['name' => 'Pemeriksa', 'role' => 'inspector']);
    $procurement->signatories()->create(['name' => 'Bendahara', 'role' => 'treasurer']);

    expect($procurement->refresh()->hasSignatories())->toBeTrue();
});

test('isReadyForDocumentGeneration tervalidasi jika semua syarat terpenuhi', function () {
    $procurement = ProcurementRequest::factory()->create([
        'subsidiary_id' => null,
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);

    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'official_price' => null
    ]);

    expect($procurement->isReadyForDocumentGeneration())->toBeFalse();

    $subsidiary = Subsidiary::factory()->create();
    $procurement->assignSubsidiary($subsidiary, User::factory()->admin()->create());

    $item->update(['official_price' => 50000]);

    $procurement->signatories()->create(['name' => 'Kepsek', 'role' => 'headmaster']);
    $procurement->signatories()->create(['name' => 'Pemeriksa', 'role' => 'inspector']);
    $procurement->signatories()->create(['name' => 'Bendahara', 'role' => 'treasurer']);

    expect($procurement->refresh()->isReadyForDocumentGeneration())->toBeTrue();
});
