<?php

use App\Livewire\Procurement\ProcurementProcessPanel;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('can process submit action from livewire panel', function () {
    $user = User::factory()->create([
        'role' => 'admin_school',
        'status' => 'active',
    ]);

    actingAs($user);

    $procurement = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_DRAFT]);
    ProcurementRequestItem::factory()->create(['procurement_request_id' => $procurement->id]);

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement])
        ->call('submitRequest')
        ->assertHasNoErrors();

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_SUBMITTED);
});

test('can process reject action with reason', function () {
    $user = User::factory()->create([
        'role' => 'admin_cv',
        'status' => 'active',
    ]);

    actingAs($user);

    $procurement = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_SUBMITTED]);

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement])
        ->set('rejectReason', 'Anggaran tidak sesuai standar')
        ->call('reject')
        ->assertSet('rejectModal', false)
        ->assertSet('rejectReason', '');

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_REJECTED);
});

test('can assign supplier via modal', function () {
    $user = User::factory()->create([
        'role' => 'admin_cv',
        'status' => 'active',
    ]);

    actingAs($user);

    $procurement = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_VERIFIED]);
    $supplier = Supplier::factory()->create();

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement])
        ->set('supplierId', $supplier->id)
        ->call('assignSupplier')
        ->assertSet('supplierModal', false);

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_SUPPLIER_ASSIGNED)
        ->and($procurement->supplier_id)->toBe($supplier->id);
});

test('reject fails validation when reason is empty', function () {
    $user = User::factory()->create([
        'role' => 'admin_cv',
        'status' => 'active',
    ]);

    actingAs($user);

    $procurement = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_SUBMITTED]);

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement])
        ->set('rejectReason', '')
        ->call('reject')
        ->assertHasErrors(['rejectReason' => 'required']);

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_SUBMITTED);
});

test('can mark items prepared and complete the process', function () {
    $user = User::factory()->create([
        'role' => 'admin_cv',
        'status' => 'active',
    ]);

    actingAs($user);

    $procurement = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_SUPPLIER_ASSIGNED]);

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement])
        ->call('markItemsPrepared')
        ->assertHasNoErrors();

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_ITEMS_PREPARED);

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement->refresh()])
        ->call('complete')
        ->assertHasNoErrors();

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_COMPLETED);
});

test('can set taxes using setTaxes method with auto calculation preview', function () {
    $user = User::factory()->create([
        'role' => 'admin_cv',
        'status' => 'active',
    ]);

    actingAs($user);

    $procurement = ProcurementRequest::factory()->create([
        'is_taxable' => true,
        'ppn_rate' => 0,
        'pph_22_rate' => 0,
        'pph_23_rate' => 0,
    ]);

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement])
        ->set('isTaxable', true)
        ->set('ppnRate', 11)
        ->set('pph22Rate', 1.5)
        ->set('pph23Rate', 2)
        ->call('setTaxes')
        ->assertHasNoErrors();

    expect($procurement->refresh())
        ->ppn_rate->toEqual(11)
        ->pph_22_rate->toEqual(1.5)
        ->pph_23_rate->toEqual(2);
});

test('can dynamically save signatories via setSignatories', function () {
    $user = User::factory()->create([
        'role' => 'admin_cv',
        'status' => 'active',
    ]);

    actingAs($user);

    $procurement = ProcurementRequest::factory()->create();

    $signatoriesData = [
        'headmaster' => ['name' => 'Budi', 'nip' => '123', 'title' => 'Kepala Sekolah'],
        'treasurer' => ['name' => 'Siti', 'nip' => '456', 'title' => 'Bendahara BOS'],
    ];

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement])
        ->set('signatoriesData', $signatoriesData)
        ->call('setSignatories')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('procurement_signatories', [
        'procurement_request_id' => $procurement->id,
        'name' => 'Budi',
    ]);
});

test('can trigger setDocumentNumbers to generate numbers via ProcurementNumberGenerator', function () {
    $user = User::factory()->create([
        'role' => 'admin_cv',
        'status' => 'active',
    ]);

    actingAs($user);

    $supplier = Supplier::factory()->create();

    $procurement = ProcurementRequest::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => ProcurementRequest::STATUS_SUPPLIER_ASSIGNED,
    ]);

    ProcurementRequestItem::factory()->count(2)->create([
        'procurement_request_id' => $procurement->id,
        'official_price' => 50000,
    ]);

    $procurement->signatories()->createMany([
        ['role' => 'headmaster', 'name' => 'Budi Santoso', 'title' => 'Kepala Sekolah'],
        ['role' => 'inspector', 'name' => 'Siti Aminah', 'title' => 'Pemeriksa Barang'],
        ['role' => 'treasurer', 'name' => 'Andi Darmawan', 'title' => 'Bendahara BOS'],
    ]);

    $procurement->documents()->create(['document_type' => 'bast', 'document_number' => null]);
    $procurement->documents()->create(['document_type' => 'purchase_order', 'document_number' => null]);

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement])
        ->call('setDocumentNumbers')
        ->assertHasNoErrors();

    $documents = $procurement->refresh()->documents;

    expect($documents)->not->toBeEmpty();

    foreach ($documents as $document) {
        expect($document->document_number)->not->toBeNull();
    }
});

test('admin_school tidak bisa memanggil verify', function () {
    $user = User::factory()->create(['role' => 'admin_school', 'status' => 'active']);
    actingAs($user);

    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_SUBMITTED,
    ]);

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement])
        ->call('verify')
        ->assertForbidden();

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_SUBMITTED);
});

test('admin_school tidak bisa assign supplier', function () {
    $user = User::factory()->create(['role' => 'admin_school', 'status' => 'active']);
    actingAs($user);

    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    $supplier = Supplier::factory()->create();

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement])
        ->set('supplierId', $supplier->id)
        ->call('assignSupplier')
        ->assertForbidden();

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_VERIFIED);
});

test('admin_cv tidak bisa submit pengajuan sekolah', function () {
    $user = User::factory()->create(['role' => 'admin_cv', 'status' => 'active']);
    actingAs($user);

    $procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
    ]);

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement])
        ->call('submitRequest')
        ->assertForbidden();

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_DRAFT);
});