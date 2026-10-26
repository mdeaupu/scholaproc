<?php

use App\Livewire\Supplier\SupplierIndex;
use App\Models\Supplier;
use App\Models\SupplierLegalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('SupplierIndex Livewire Component', function () {
    it('renders successfully for authorized user', function () {
        $user = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($user);

        Livewire::test(SupplierIndex::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.supplier.supplier-index');
    });

    it('aborts for unauthorized user', function () {
        $user = User::factory()->create(['role' => 'school']);
        $this->actingAs($user);

        Livewire::test(SupplierIndex::class)
            ->assertStatus(403);
    });

    it('admin cv can view supplier index in read-only mode', function () {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user);

        Livewire::test(SupplierIndex::class)
            ->assertStatus(200)
            ->assertSee('Manajemen Supplier')
            ->assertDontSee('Tambah Supplier');
    });

    it('admin cv cannot destroy supplier', function () {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user);

        $supplier = Supplier::factory()->create();

        Livewire::test(SupplierIndex::class)
            ->call('confirmDestroy', $supplier->id, $supplier->company_name)
            ->assertStatus(403);

        expect($supplier->fresh()->trashed())->toBeFalse();
    });

    it('can search suppliers', function () {
        $user = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($user);

        Supplier::factory()->create(['company_name' => 'PT Maju Jaya']);
        Supplier::factory()->create(['company_name' => 'CV Sejahtera']);

        Livewire::test(SupplierIndex::class)
            ->set('search', 'Maju')
            ->assertSee('PT Maju Jaya')
            ->assertDontSee('CV Sejahtera');
    });

    it('can show supplier detail', function () {
        $user = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $doc = SupplierLegalDocument::factory()->for($supplier)->deed()->create();

        Livewire::test(SupplierIndex::class)
            ->call('show', $supplier->id)
            ->assertSet('detailModal', true)
            ->assertSet('selectedSupplier.id', $supplier->id)
            ->assertSee($supplier->company_name)
            ->assertSee($doc->document_number);
    });

    it('can confirm destroy', function () {
        $user = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($user);

        $supplier = Supplier::factory()->create();

        Livewire::test(SupplierIndex::class)
            ->call('confirmDestroy', $supplier->id, $supplier->company_name)
            ->assertSet('confirmingDestroy', true)
            ->assertSet('targetSupplierId', $supplier->id)
            ->assertSet('targetSupplierName', $supplier->company_name);
    });

    it('can destroy supplier (soft delete)', function () {
        $user = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $doc = SupplierLegalDocument::factory()->for($supplier)->create();

        Livewire::test(SupplierIndex::class)
            ->call('confirmDestroy', $supplier->id, $supplier->company_name)
            ->call('destroy')
            ->assertSet('confirmingDestroy', false)
            ->assertSet('targetSupplierId', null);

        expect($supplier->fresh()->trashed())->toBeTrue()
            ->and($doc->fresh())->toBeNull();
    });

    it('can search suppliers by address', function () {
        $user = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($user);

        Supplier::factory()->create([
            'company_name' => 'PT Alpha',
            'address' => 'Jl. Uji Khusus Alamat No. 1',
        ]);
        Supplier::factory()->create([
            'company_name' => 'PT Beta',
            'address' => 'Jl. Lain Sekali No. 2',
        ]);

        Livewire::test(SupplierIndex::class)
            ->set('search', 'Uji Khusus')
            ->assertSee('PT Alpha')
            ->assertDontSee('PT Beta');
    });

    it('shows supplier statistics', function () {
        $user = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($user);

        $complete = Supplier::factory()->create();
        SupplierLegalDocument::factory()->for($complete)->deed()->create();
        SupplierLegalDocument::factory()->for($complete)->businessPermit()->permanent()->create();

        $incomplete = Supplier::factory()->create();
        SupplierLegalDocument::factory()->for($incomplete)->deed()->create();

        $component = Livewire::test(SupplierIndex::class);

        expect($component->viewData('totalSuppliers'))->toBe(2)
            ->and($component->viewData('completeLegalSuppliers'))->toBe(1)
            ->and($component->viewData('certifiedSuppliers'))->toBe(1);
    });

    it('can filter suppliers by legalitas completeness', function () {
        $user = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($user);

        $complete = Supplier::factory()->create(['company_name' => 'PT Legal Lengkap']);
        SupplierLegalDocument::factory()->for($complete)->deed()->create();
        SupplierLegalDocument::factory()->for($complete)->businessPermit()->permanent()->create();

        $incomplete = Supplier::factory()->create(['company_name' => 'PT Belum Lengkap']);
        SupplierLegalDocument::factory()->for($incomplete)->deed()->create();

        Livewire::test(SupplierIndex::class)
            ->set('filterLegalitas', 'lengkap')
            ->assertSee('PT Legal Lengkap')
            ->assertDontSee('PT Belum Lengkap');

        Livewire::test(SupplierIndex::class)
            ->set('filterLegalitas', 'belum')
            ->assertSee('PT Belum Lengkap')
            ->assertDontSee('PT Legal Lengkap');
    });
});
