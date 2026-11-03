<?php

use App\Livewire\Master\PackageCategoryIndex;
use App\Models\PackageCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('superadmin dapat mengelola kategori paket', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(PackageCategoryIndex::class)
        ->assertStatus(200)
        ->assertSee('Manajemen Kategori Paket')
        ->assertSee('Tambah Kategori');
});

test('admin tidak dapat mengakses pengelolaan kategori paket', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(PackageCategoryIndex::class)
        ->assertStatus(403);
});

test('tabel kategori paket dan sumber dana tidak memiliki kolom kode atau deskripsi', function () {
    foreach (['package_categories', 'funding_sources'] as $table) {
        expect(Schema::hasColumn($table, 'code'))->toBeFalse()
            ->and(Schema::hasColumn($table, 'description'))->toBeFalse();
    }
});

test('kategori paket dapat dibuat dengan nama dan status', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(PackageCategoryIndex::class)
        ->set('nama_kategori', 'Sarana Pendidikan')
        ->set('is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('package_categories', [
        'name' => 'Sarana Pendidikan',
        'is_active' => false,
    ]);
});

test('kategori paket dapat dicari dan difilter berdasarkan status', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $active = PackageCategory::factory()->create([
        'name' => 'Perlengkapan Kelas',
        'is_active' => true,
    ]);
    PackageCategory::factory()->create([
        'name' => 'Peralatan Lama',
        'is_active' => false,
    ]);

    Livewire::test(PackageCategoryIndex::class)
        ->set('search', 'Perlengkapan')
        ->set('filterStatus', 'active')
        ->assertViewHas('categories', fn($categories) => $categories->total() === 1
            && $categories->contains($active));
});

test('kategori paket dapat diperbarui melalui modal', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $category = PackageCategory::factory()->create(['name' => 'Kategori Awal']);

    Livewire::test(PackageCategoryIndex::class)
        ->call('openModal', $category->id)
        ->set('nama_kategori', 'Kategori Diperbarui')
        ->call('save')
        ->assertHasNoErrors();

    expect($category->fresh()->name)->toBe('Kategori Diperbarui');
});