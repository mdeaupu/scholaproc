<?php

use App\Livewire\Master\ItemUnitIndex;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('superadmin dapat mengelola satuan barang', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(ItemUnitIndex::class)
        ->assertStatus(200)
        ->assertSee('Manajemen Satuan Barang')
        ->assertSee('Tambah Satuan Barang');
});

test('admin tidak dapat mengakses pengelolaan satuan barang', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ItemUnitIndex::class)
        ->assertStatus(403);
});

test('satuan barang dapat dibuat beserta statusnya', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(ItemUnitIndex::class)
        ->set('nama_satuan', 'Pcs')
        ->set('is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('item_units', [
        'name' => 'Pcs',
        'is_active' => false,
    ]);
});

test('satuan barang dapat dicari dan difilter berdasarkan status', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $active = ItemUnit::factory()->create([
        'name' => 'Kilogram',
        'is_active' => true,
    ]);
    ItemUnit::factory()->create([
        'name' => 'Liter Lama',
        'is_active' => false,
    ]);

    Livewire::test(ItemUnitIndex::class)
        ->set('search', 'Kilo')
        ->set('filterStatus', 'active')
        ->assertViewHas('units', fn($units) => $units->total() === 1
            && $units->contains($active));
});

test('satuan barang dapat diperbarui melalui modal', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $unit = ItemUnit::factory()->create(['name' => 'Pcs']);

    Livewire::test(ItemUnitIndex::class)
        ->call('openModal', $unit->id)
        ->set('nama_satuan', 'Box')
        ->set('is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($unit->fresh()->name)->toBe('Box')
        ->and($unit->fresh()->is_active)->toBeFalse();
});

test('satuan yang dipakai transaksi tidak dapat dihapus', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $unit = ItemUnit::factory()->create();

    $request = \App\Models\ProcurementRequest::factory()->create();
    \App\Models\ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'unit_id' => $unit->id,
    ]);

    Livewire::test(ItemUnitIndex::class)
        ->call('delete', $unit->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('item_units', ['id' => $unit->id]);
});