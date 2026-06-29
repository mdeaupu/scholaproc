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
    $user = User::factory()->create();
    actingAs($user);

    $procurement = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_DRAFT]);
    ProcurementRequestItem::factory()->create(['procurement_request_id' => $procurement->id]);

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement])
        ->call('submitRequest')
        ->assertHasNoErrors();

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_SUBMITTED);
});

test('can process reject action with reason', function () {
    $user = User::factory()->create();
    actingAs($user);

    $procurement = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_SUBMITTED]);

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement])
        ->set('rejectReason', 'Anggaran tidak sesuai standar')
        ->call('reject')
        ->assertSet('rejectModal', false)
        ->assertSet('rejectReason', '');

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_REJECTED);

    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $procurement->id,
        'status' => ProcurementRequest::STATUS_REJECTED,
        'notes' => 'Ditolak: Anggaran tidak sesuai standar'
    ]);
});

test('can assign supplier via modal', function () {
    $user = User::factory()->create();
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
    $user = User::factory()->create();
    actingAs($user);

    $procurement = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_SUBMITTED]);

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $procurement])
        ->set('rejectReason', '')
        ->call('reject')
        ->assertHasErrors(['rejectReason' => 'required']);

    expect($procurement->refresh()->status)->toBe(ProcurementRequest::STATUS_SUBMITTED);
});

test('can mark items prepared and complete the process', function () {
    $user = User::factory()->create();
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

    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $procurement->id,
        'status' => ProcurementRequest::STATUS_COMPLETED,
    ]);
});