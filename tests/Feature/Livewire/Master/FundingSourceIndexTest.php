<?php

use App\Livewire\Master\FundingSourceIndex;
use App\Models\FundingSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('superadmin dapat mengelola sumber dana', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(FundingSourceIndex::class)
        ->assertStatus(200)
        ->assertSee('Manajemen Sumber Dana')
        ->assertSee('Tambah Sumber Dana');
});

test('admin tidak dapat mengakses pengelolaan sumber dana', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(FundingSourceIndex::class)
        ->assertStatus(403);
});

test('sumber dana dapat dibuat dengan nama dan status', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(FundingSourceIndex::class)
        ->set('nama_sumber_dana', 'BOSP Reguler')
        ->set('is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('funding_sources', [
        'name' => 'BOSP Reguler',
        'is_active' => false,
    ]);
});

test('sumber dana dapat dicari dan difilter berdasarkan status', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $active = FundingSource::factory()->create([
        'name' => 'BOSP Reguler',
        'is_active' => true,
    ]);
    FundingSource::factory()->create([
        'name' => 'BOP Khusus',
        'is_active' => false,
    ]);

    Livewire::test(FundingSourceIndex::class)
        ->set('search', 'Reguler')
        ->set('filterStatus', 'active')
        ->assertViewHas('sources', fn($sources) => $sources->total() === 1
            && $sources->contains($active));
});

test('sumber dana dapat diperbarui melalui modal', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $source = FundingSource::factory()->create(['name' => 'Sumber Dana Awal']);

    Livewire::test(FundingSourceIndex::class)
        ->call('openModal', $source->id)
        ->set('nama_sumber_dana', 'BOSP Diperbarui')
        ->call('save')
        ->assertHasNoErrors();

    expect($source->fresh()->name)->toBe('BOSP Diperbarui');
});