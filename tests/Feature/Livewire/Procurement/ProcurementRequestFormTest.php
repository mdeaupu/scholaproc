<?php

use App\Livewire\Procurement\ProcurementRequestForm;
use App\Models\BudgetYear;
use App\Models\FundingSource;
use App\Models\ItemUnit;
use App\Models\PackageCategory;
use App\Models\ProcurementRequest;
use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('can render the form component', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => $school->id]);
    actingAs($user);

    Livewire::test(ProcurementRequestForm::class)
        ->assertStatus(200)
        ->assertViewIs('livewire.procurement.procurement-request-form');
});

test('can add and remove items dynamically', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => $school->id]);
    actingAs($user);

    Livewire::test(ProcurementRequestForm::class)
        ->call('addItem')
        ->assertCount('items', 2)
        ->call('removeItem', 0)
        ->assertCount('items', 1);
});

test('can save a new draft procurement request', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => $school->id]);
    actingAs($user);

    $category = PackageCategory::factory()->create(['name' => 'Alat Tulis Kantor', 'is_active' => true]);
    $budgetYear = BudgetYear::factory()->create(['name' => '2025/2026', 'is_active' => true]);
    $fundingSource = FundingSource::factory()->create(['name' => 'BOSP Reguler', 'is_active' => true]);
    $unit = ItemUnit::factory()->create(['name' => 'Rim', 'is_active' => true]);

    Livewire::test(ProcurementRequestForm::class)
        ->set('package_category_id', $category->id)
        ->set('budget_year_id', $budgetYear->id)
        ->set('funding_source_id', $fundingSource->id)
        ->set('items.0.item_name', 'Kertas HVS')
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

test('prevents editing if status is not draft', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => $school->id]);
    actingAs($user);

    $procurement = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_SUBMITTED,
    ]);

    Livewire::test(ProcurementRequestForm::class, ['id' => $procurement->id])
        ->assertSessionHas('error')
        ->assertRedirect(route('procurement.index'));
});

test('fails validation when required fields are empty', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => $school->id]);
    actingAs($user);

    Livewire::test(ProcurementRequestForm::class)
        ->set('package_category_id', '')
        ->set('budget_year_id', '')
        ->set('funding_source_id', '')
        ->call('save')
        ->assertHasErrors([
            'package_category_id' => 'required',
            'budget_year_id' => 'required',
            'funding_source_id' => 'required',
        ]);

    $this->assertDatabaseCount('procurement_requests', 0);
});

test('cannot remove last remaining item', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => $school->id]);
    actingAs($user);

    Livewire::test(ProcurementRequestForm::class)
        ->assertCount('items', 1)
        ->call('removeItem', 0)
        ->assertCount('items', 1);
});

test('computes estimated subtotal in real time', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => $school->id]);
    actingAs($user);

    Livewire::test(ProcurementRequestForm::class)
        ->set('items.0.quantity', 5)
        ->set('items.0.estimated_price', 20000)
        ->assertSet('estimatedSubtotal', 100000);
});
