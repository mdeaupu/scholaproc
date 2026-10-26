<?php

use App\Livewire\Procurement\ProcurementRequestForm;
use App\Models\BudgetYear;
use App\Models\FundingSource;
use App\Models\ItemUnit;
use App\Models\PackageCategory;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('can render the form component', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    Livewire::test(ProcurementRequestForm::class)
        ->assertStatus(200)
        ->assertViewIs('livewire.procurement.procurement-request-form')
        ->assertSee('Kembali ke Daftar Pengajuan')
        ->assertSee('Simpan Draf')
        ->assertSee('Kirim Pengajuan')
        ->assertDontSee('Batal');
});

test('can add and remove items dynamically', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    Livewire::test(ProcurementRequestForm::class)
        ->assertSee('1 barang')
        ->call('addItem')
        ->assertCount('items', 2)
        ->assertSee('2 barang')
        ->call('removeItem', 0)
        ->assertCount('items', 1)
        ->assertSee('1 barang');
});

test('dropdown selections update the form and summary immediately', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    $category = PackageCategory::factory()->create(['name' => 'Kategori Reaktif', 'is_active' => true]);
    $budgetYear = BudgetYear::factory()->create(['name' => 'Tahun Reaktif', 'is_active' => true]);
    $fundingSource = FundingSource::factory()->create(['name' => 'Dana Reaktif', 'is_active' => true]);
    $unit = ItemUnit::factory()->create(['name' => 'Satuan Reaktif', 'is_active' => true]);
    $school->fundingSources()->attach($fundingSource);

    Livewire::test(ProcurementRequestForm::class)
        ->assertSeeHtml('wire:model.live="package_category_id"')
        ->assertSeeHtml('wire:model.live="funding_source_id"')
        ->assertSeeHtml('wire:model.live="budget_year_id"')
        ->assertSeeHtml('wire:model.live="items.0.unit_id"')
        ->set('package_category_id', (string) $category->id)
        ->set('funding_source_id', (string) $fundingSource->id)
        ->set('budget_year_id', (string) $budgetYear->id)
        ->set('items.0.unit_id', (string) $unit->id)
        ->assertSet('package_category_id', (string) $category->id)
        ->assertSet('funding_source_id', (string) $fundingSource->id)
        ->assertSet('budget_year_id', (string) $budgetYear->id)
        ->assertSet('items.0.unit_id', (string) $unit->id)
        ->assertSee('Kategori Reaktif')
        ->assertSee('Dana Reaktif')
        ->assertSee('Tahun Reaktif');
});

test('can save a new draft procurement request', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    $category = PackageCategory::factory()->create(['name' => 'Alat Tulis Kantor', 'is_active' => true]);
    $budgetYear = BudgetYear::factory()->create(['name' => '2025/2026', 'is_active' => true]);
    $fundingSource = FundingSource::factory()->create(['name' => 'BOSP Reguler', 'is_active' => true]);
    $unit = ItemUnit::factory()->create(['name' => 'Rim', 'is_active' => true]);
    $school->fundingSources()->attach($fundingSource);

    Livewire::test(ProcurementRequestForm::class)
        ->set('package_category_id', $category->id)
        ->set('budget_year_id', $budgetYear->id)
        ->set('funding_source_id', $fundingSource->id)
        ->set('items.0.item_name', 'Kertas HVS')
        ->set('items.0.specification', 'A4 80 gram')
        ->set('items.0.quantity', 10)
        ->set('items.0.unit_id', $unit->id)
        ->set('items.0.estimated_price', 50000)
        ->call('save')
        ->assertSessionHas('toast_success')
        ->assertRedirect();

    $this->assertDatabaseHas('procurement_requests', [
        'package_category_id' => $category->id,
        'budget_year_id' => $budgetYear->id,
        'funding_source_id' => $fundingSource->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
        'school_id' => $school->id,
    ]);

    $this->assertDatabaseHas('procurement_request_items', [
        'item_name' => 'Kertas HVS',
        'estimated_price' => 50000,
        'line_number' => 1,
        'unit_id' => $unit->id,
    ]);
});

test('can create and submit an application in one action', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    $category = PackageCategory::factory()->create(['is_active' => true]);
    $budgetYear = BudgetYear::factory()->create(['is_active' => true]);
    $fundingSource = FundingSource::factory()->create(['is_active' => true]);
    $unit = ItemUnit::factory()->create(['is_active' => true]);
    $school->fundingSources()->attach($fundingSource);

    Livewire::test(ProcurementRequestForm::class)
        ->set('package_category_id', $category->id)
        ->set('budget_year_id', $budgetYear->id)
        ->set('funding_source_id', $fundingSource->id)
        ->set('items.0.item_name', 'Meja siswa')
        ->set('items.0.specification', 'Kayu, 120 x 60 cm')
        ->set('items.0.quantity', 4)
        ->set('items.0.unit_id', $unit->id)
        ->set('items.0.estimated_price', 750000)
        ->call('submitRequest')
        ->assertHasNoErrors()
        ->assertSessionHas('toast_success')
        ->assertRedirect();

    $request = ProcurementRequest::query()->sole();
    expect($request->status)->toBe(ProcurementRequest::STATUS_SUBMITTED)
        ->and($request->requested_at)->not->toBeNull();

    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $request->id,
        'status' => ProcurementRequest::STATUS_SUBMITTED,
    ]);
});

test('draft can be saved before funding source is assigned but submit cannot', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    $category = PackageCategory::factory()->create(['is_active' => true]);
    $budgetYear = BudgetYear::factory()->create(['is_active' => true]);
    $fundingSource = FundingSource::factory()->create(['is_active' => true]);
    $unit = ItemUnit::factory()->create(['is_active' => true]);

    Livewire::test(ProcurementRequestForm::class)
        ->set('package_category_id', $category->id)
        ->set('budget_year_id', $budgetYear->id)
        ->set('funding_source_id', $fundingSource->id)
        ->set('items.0.item_name', 'Meja siswa')
        ->set('items.0.specification', 'Kayu, 120 x 60 cm')
        ->set('items.0.quantity', 4)
        ->set('items.0.unit_id', $unit->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $request = ProcurementRequest::query()->sole();

    expect($request->status)->toBe(ProcurementRequest::STATUS_DRAFT)
        ->and($request->funding_source_id)->toBe($fundingSource->id);

    Livewire::test(ProcurementRequestForm::class, ['id' => $request->id])
        ->call('submitRequest')
        ->assertHasErrors(['funding_source_id']);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_DRAFT);
});

test('draft can retain inactive master selections while submit rejects them', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    $category = PackageCategory::factory()->create(['is_active' => false]);
    $budgetYear = BudgetYear::factory()->create(['is_active' => false]);
    $fundingSource = FundingSource::factory()->create(['is_active' => false]);
    $unit = ItemUnit::factory()->create(['is_active' => false]);
    $school->fundingSources()->attach($fundingSource);

    Livewire::test(ProcurementRequestForm::class)
        ->set('package_category_id', $category->id)
        ->set('budget_year_id', $budgetYear->id)
        ->set('funding_source_id', $fundingSource->id)
        ->set('items.0.item_name', 'Meja siswa')
        ->set('items.0.specification', 'Kayu, 120 x 60 cm')
        ->set('items.0.quantity', 4)
        ->set('items.0.unit_id', $unit->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $request = ProcurementRequest::query()->sole();

    Livewire::test(ProcurementRequestForm::class, ['id' => $request->id])
        ->call('submitRequest')
        ->assertHasErrors([
            'package_category_id',
            'budget_year_id',
            'funding_source_id',
            'items.0.unit_id',
        ]);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_DRAFT);
});

test('prevents editing if status is not draft', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    $procurement = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_SUBMITTED,
    ]);

    Livewire::test(ProcurementRequestForm::class, ['id' => $procurement->id])
        ->assertSessionHas('error')
        ->assertRedirect(route('procurement.index'));
});

test('school can edit a request that needs revision and the edit is recorded', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    $category = PackageCategory::factory()->create(['is_active' => true]);
    $budgetYear = BudgetYear::factory()->create(['is_active' => true]);
    $fundingSource = FundingSource::factory()->create(['is_active' => true]);
    $revisedCategory = PackageCategory::factory()->create(['is_active' => true]);
    $school->fundingSources()->attach($fundingSource);
    $unit = ItemUnit::factory()->create();
    $procurement = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'package_category_id' => $category->id,
        'budget_year_id' => $budgetYear->id,
        'funding_source_id' => $fundingSource->id,
        'status' => ProcurementRequest::STATUS_NEEDS_REVISION,
    ]);
    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'unit_id' => $unit->id,
        'item_name' => 'Barang sebelum revisi',
    ]);

    Livewire::test(ProcurementRequestForm::class, ['id' => $procurement->id])
        ->set('package_category_id', $revisedCategory->id)
        ->set('items.0.item_name', 'Barang hasil revisi')
        ->call('save')
        ->assertRedirect();

    expect($procurement->fresh()->status)->toBe(ProcurementRequest::STATUS_NEEDS_REVISION);
    expect($item->fresh()->item_name)->toBe('Barang hasil revisi')
        ->and(ProcurementRequestItem::query()->where('procurement_request_id', $procurement->id)->count())->toBe(1);

    $history = $procurement->histories()->latest('id')->firstOrFail();
    $audit = json_decode(substr($history->notes, strlen('Perubahan revisi: ')), true, flags: JSON_THROW_ON_ERROR);

    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $procurement->id,
        'status' => ProcurementRequest::STATUS_NEEDS_REVISION,
    ]);
    expect($audit['request']['package_category_id'])->toBe([
        'before' => $category->id,
        'after' => $revisedCategory->id,
    ]);
    expect($audit['items']['updated'][$item->id]['item_name'])->toBe([
        'before' => 'Barang sebelum revisi',
        'after' => 'Barang hasil revisi',
    ]);
});

test('revision audit records added and removed item values', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    $category = PackageCategory::factory()->create(['is_active' => true]);
    $budgetYear = BudgetYear::factory()->create(['is_active' => true]);
    $fundingSource = FundingSource::factory()->create(['is_active' => true]);
    $school->fundingSources()->attach($fundingSource);
    $unit = ItemUnit::factory()->create(['is_active' => true]);
    $procurement = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'package_category_id' => $category->id,
        'budget_year_id' => $budgetYear->id,
        'funding_source_id' => $fundingSource->id,
        'status' => ProcurementRequest::STATUS_NEEDS_REVISION,
    ]);
    $removedItem = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'line_number' => 1,
        'unit_id' => $unit->id,
        'item_name' => 'Barang dihapus',
    ]);
    $retainedItem = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $procurement->id,
        'line_number' => 2,
        'unit_id' => $unit->id,
        'item_name' => 'Barang dipertahankan',
    ]);

    Livewire::test(ProcurementRequestForm::class, ['id' => $procurement->id])
        ->call('removeItem', 0)
        ->call('addItem')
        ->set('items.1.item_name', 'Barang baru')
        ->set('items.1.specification', 'Spesifikasi baru')
        ->set('items.1.quantity', 2)
        ->set('items.1.unit_id', $unit->id)
        ->set('items.1.estimated_price', 15000)
        ->call('save')
        ->assertRedirect();

    $this->assertSoftDeleted('procurement_request_items', ['id' => $removedItem->id]);
    expect($retainedItem->fresh()->item_name)->toBe('Barang dipertahankan');

    $history = $procurement->histories()->latest('id')->firstOrFail();
    $audit = json_decode(substr($history->notes, strlen('Perubahan revisi: ')), true, flags: JSON_THROW_ON_ERROR);

    expect($audit['items']['deleted'][$removedItem->id]['item_name'])->toBe('Barang dihapus')
        ->and($audit['items']['created'][0]['values']['item_name'])->toBe('Barang baru')
        ->and($audit['items']['created'][0]['values']['quantity'])->toBe(2);
});

test('requires a non-placeholder specification for each item on submit', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    $category = PackageCategory::factory()->create(['is_active' => true]);
    $budgetYear = BudgetYear::factory()->create(['is_active' => true]);
    $fundingSource = FundingSource::factory()->create(['is_active' => true]);
    $unit = ItemUnit::factory()->create(['is_active' => true]);
    $school->fundingSources()->attach($fundingSource);

    Livewire::test(ProcurementRequestForm::class)
        ->set('package_category_id', $category->id)
        ->set('budget_year_id', $budgetYear->id)
        ->set('funding_source_id', $fundingSource->id)
        ->set('items.0.item_name', 'Meja siswa')
        ->set('items.0.quantity', 4)
        ->set('items.0.unit_id', $unit->id)
        ->call('submitRequest')
        ->assertHasErrors(['items.0.specification' => 'required'])
        ->set('items.0.specification', '-')
        ->call('submitRequest')
        ->assertHasErrors(['items.0.specification' => 'not_in']);

    $this->assertDatabaseCount('procurement_requests', 0);
});

test('can save a completely empty draft', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    Livewire::test(ProcurementRequestForm::class)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSessionHas('toast_success')
        ->assertRedirect();

    $request = ProcurementRequest::query()->sole();

    expect($request->status)->toBe(ProcurementRequest::STATUS_DRAFT)
        ->and($request->package_category_id)->toBeNull()
        ->and($request->budget_year_id)->toBeNull()
        ->and($request->funding_source_id)->toBeNull();

    $this->assertDatabaseCount('procurement_request_items', 0);
});

test('can save a partial item in a draft', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    Livewire::test(ProcurementRequestForm::class)
        ->set('items.0.item_name', 'Kertas HVS')
        ->set('items.0.quantity', '')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSessionHas('toast_success')
        ->assertRedirect();

    $item = ProcurementRequestItem::query()->sole();

    expect($item->item_name)->toBe('Kertas HVS')
        ->and($item->specification)->toBeNull()
        ->and($item->unit_id)->toBeNull()
        ->and($item->quantity)->toBeNull()
        ->and($item->estimated_price)->toBeNull();
});

test('draft accepts zero quantity but submit requires positive quantity', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    $category = PackageCategory::factory()->create(['is_active' => true]);
    $budgetYear = BudgetYear::factory()->create(['is_active' => true]);
    $fundingSource = FundingSource::factory()->create(['is_active' => true]);
    $unit = ItemUnit::factory()->create(['is_active' => true]);
    $school->fundingSources()->attach($fundingSource);

    Livewire::test(ProcurementRequestForm::class)
        ->set('package_category_id', $category->id)
        ->set('budget_year_id', $budgetYear->id)
        ->set('funding_source_id', $fundingSource->id)
        ->set('items.0.item_name', 'Meja siswa')
        ->set('items.0.specification', 'Kayu')
        ->set('items.0.quantity', 0)
        ->set('items.0.unit_id', $unit->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $request = ProcurementRequest::query()->sole();
    expect($request->items()->sole()->quantity)->toBe(0);

    Livewire::test(ProcurementRequestForm::class, ['id' => $request->id])
        ->call('submitRequest')
        ->assertHasErrors(['items.0.quantity' => 'min']);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_DRAFT);
});

test('fails validation when required fields are empty on submit', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    Livewire::test(ProcurementRequestForm::class)
        ->set('package_category_id', '')
        ->set('budget_year_id', '')
        ->set('funding_source_id', '')
        ->call('submitRequest')
        ->assertHasErrors([
            'package_category_id' => 'required',
            'budget_year_id' => 'required',
            'funding_source_id' => 'required',
        ]);

    $this->assertDatabaseCount('procurement_requests', 0);
});

test('cannot remove last remaining item', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    Livewire::test(ProcurementRequestForm::class)
        ->assertCount('items', 1)
        ->call('removeItem', 0)
        ->assertCount('items', 1);
});

test('computes estimated subtotal in real time', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    actingAs($user);

    Livewire::test(ProcurementRequestForm::class)
        ->set('items.0.quantity', 5)
        ->set('items.0.estimated_price', 20000)
        ->assertSet('estimatedSubtotal', 100000);
});
