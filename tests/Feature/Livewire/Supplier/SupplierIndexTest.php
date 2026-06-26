<?php

use App\Livewire\Supplier\SupplierIndex;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('SupplierIndex Livewire Component', function () {
    it('renders successfully for authorized user', function () {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user);

        Livewire::test(SupplierIndex::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.supplier.supplier-index');
    });

    it('aborts for unauthorized user', function () {
        $user = User::factory()->create(['role' => 'admin_school']);
        $this->actingAs($user);

        Livewire::test(SupplierIndex::class)
            ->assertStatus(403);
    });

    it('can search suppliers', function () {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user);

        Supplier::factory()->create(['company_name' => 'PT Maju Jaya']);
        Supplier::factory()->create(['company_name' => 'CV Sejahtera']);

        Livewire::test(SupplierIndex::class)
            ->set('search', 'Maju')
            ->assertSee('PT Maju Jaya')
            ->assertDontSee('CV Sejahtera');
    });

    it('can show supplier detail', function () {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $doc = \App\Models\SupplierLegalDocument::factory()->for($supplier)->deed()->create();

        Livewire::test(SupplierIndex::class)
            ->call('show', $supplier->id)
            ->assertSet('detailModal', true)
            ->assertSet('selectedSupplier.id', $supplier->id)
            ->assertSee($supplier->company_name)
            ->assertSee($doc->document_number);
    });

    it('can confirm destroy', function () {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user);

        $supplier = Supplier::factory()->create();

        Livewire::test(SupplierIndex::class)
            ->call('confirmDestroy', $supplier->id, $supplier->company_name)
            ->assertSet('confirmingDestroy', true)
            ->assertSet('targetSupplierId', $supplier->id)
            ->assertSet('targetSupplierName', $supplier->company_name);
    });

    it('can destroy supplier (soft delete)', function () {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $doc = \App\Models\SupplierLegalDocument::factory()->for($supplier)->create();

        Livewire::test(SupplierIndex::class)
            ->call('confirmDestroy', $supplier->id, $supplier->company_name)
            ->call('destroy')
            ->assertSet('confirmingDestroy', false)
            ->assertSet('targetSupplierId', null);

        expect($supplier->fresh()->trashed())->toBeTrue()
            ->and($doc->fresh())->toBeNull();
    });
});