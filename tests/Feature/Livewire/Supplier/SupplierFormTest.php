<?php

use App\Livewire\Supplier\SupplierForm;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('SupplierForm Livewire Component', function () {
    it('renders create form for authorized user', function () {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user);

        Livewire::test(SupplierForm::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.supplier.supplier-form')
            ->assertSet('isEdit', false);
    });

    it('renders edit form with existing data', function () {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user);

        $supplier = Supplier::factory()->create();

        Livewire::test(SupplierForm::class, ['supplier' => $supplier])
            ->assertSet('isEdit', true)
            ->assertSet('company_name', $supplier->company_name)
            ->assertSet('npwp', $supplier->npwp);
    });

    it('can store new supplier', function () {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user);

        $data = Supplier::factory()->make()->toArray();

        Livewire::test(SupplierForm::class)
            ->set('company_name', $data['company_name'])
            ->set('pic_name', $data['pic_name'])
            ->set('director_name', $data['director_name'])
            ->set('director_nik', $data['director_nik'])
            ->set('npwp', $data['npwp'])
            ->set('nib', $data['nib'])
            ->set('phone', $data['phone'])
            ->set('email', $data['email'])
            ->set('address', $data['address'])
            ->call('save')
            ->assertRedirect(route('owner.suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'company_name' => $data['company_name'],
            'npwp' => $data['npwp'],
        ]);
    });

    it('can update existing supplier', function () {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $newName = 'Updated Company';

        Livewire::test(SupplierForm::class, ['supplier' => $supplier])
            ->set('company_name', $newName)
            ->call('save')
            ->assertRedirect(route('owner.suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'company_name' => $newName,
        ]);
    });

    it('validates uniqueness of npwp and nib', function () {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user);

        $existing = Supplier::factory()->create(['npwp' => '1234567890', 'nib' => '12345678901234']);

        Livewire::test(SupplierForm::class)
            ->set('company_name', 'Test')
            ->set('pic_name', 'Test')
            ->set('director_name', 'Test')
            ->set('director_nik', '1234567890123456')
            ->set('npwp', $existing->npwp)
            ->set('nib', '11111111111111')
            ->set('phone', '08123456789')
            ->set('address', 'Jl. Test')
            ->call('save')
            ->assertHasErrors(['npwp']);
    });
});