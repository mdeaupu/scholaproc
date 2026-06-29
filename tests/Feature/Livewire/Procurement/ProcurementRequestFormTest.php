<?php

use App\Livewire\Procurement\ProcurementRequestForm;
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

    Livewire::test(ProcurementRequestForm::class)
        ->set('package_category', 'Alat Tulis Kantor')
        ->set('budget_year', '2024')
        ->set('funding_source', 'BOSP')
        ->set('items.0.item_name', 'Kertas HVS')
        ->set('items.0.quantity', 10)
        ->set('items.0.unit', 'Rim')
        ->set('items.0.estimated_price', 50000)
        ->call('save')
        ->assertSessionHas('toast_success')
        ->assertRedirect();

    $this->assertDatabaseHas('procurement_requests', [
        'package_category' => 'Alat Tulis Kantor',
        'status' => ProcurementRequest::STATUS_DRAFT,
        'school_id' => $school->id,
    ]);

    $this->assertDatabaseHas('procurement_request_items', [
        'item_name' => 'Kertas HVS',
        'estimated_price' => 50000,
        'line_number' => 1,
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
        ->set('package_category', '')
        ->set('budget_year', '')
        ->set('funding_source', '')
        ->call('save')
        ->assertHasErrors([
            'package_category' => 'required',
            'budget_year' => 'required',
            'funding_source' => 'required',
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