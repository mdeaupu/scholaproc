<?php

use App\Livewire\Master\BudgetYearIndex;
use App\Models\BudgetYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('superadmin dapat mengelola tahun anggaran', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(BudgetYearIndex::class)
        ->assertStatus(200)
        ->assertSee('Manajemen Tahun Anggaran')
        ->assertSee('Tambah Tahun Anggaran');
});

test('admin tidak dapat mengakses pengelolaan tahun anggaran', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(BudgetYearIndex::class)
        ->assertStatus(403);
});

test('tahun anggaran dapat dibuat dan diaktifkan', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $previous = BudgetYear::factory()->create([
        'name' => '2029',
        'start_date' => '2029-01-01',
        'end_date' => '2029-12-31',
        'is_active' => true,
    ]);

    Livewire::test(BudgetYearIndex::class)
        ->set('tahun', '2030/2031')
        ->set('tanggal_mulai', '2030-01-01')
        ->set('tanggal_selesai', '2030-12-31')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('budget_years', [
        'name' => '2030/2031',
        'is_active' => true,
    ]);
    $created = BudgetYear::where('name', '2030/2031')->firstOrFail();
    expect(Carbon::parse($created->start_date)->toDateString())->toBe('2030-01-01')
        ->and(Carbon::parse($created->end_date)->toDateString())->toBe('2030-12-31');
    expect($previous->fresh()->is_active)->toBeFalse();
});

test('tahun anggaran dapat dicari dan difilter berdasarkan status', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $active = BudgetYear::factory()->create([
        'name' => '2031',
        'start_date' => '2031-01-01',
        'end_date' => '2031-12-31',
        'is_active' => true,
    ]);
    BudgetYear::factory()->create([
        'name' => '2032',
        'start_date' => '2032-01-01',
        'end_date' => '2032-12-31',
        'is_active' => false,
    ]);

    Livewire::test(BudgetYearIndex::class)
        ->set('search', '2031')
        ->set('filterStatus', 'active')
        ->assertViewHas('budgetYears', fn($years) => $years->total() === 1
            && $years->contains($active));
});

test('tahun anggaran dapat diperbarui melalui modal', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $year = BudgetYear::factory()->create([
        'name' => '2033',
        'start_date' => '2033-01-01',
        'end_date' => '2033-12-31',
    ]);

    Livewire::test(BudgetYearIndex::class)
        ->call('openModal', $year->id)
        ->set('tahun', '2034')
        ->set('tanggal_mulai', '2034-01-01')
        ->set('tanggal_selesai', '2034-12-31')
        ->set('is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    $updated = $year->fresh();

    expect($updated->name)->toBe('2034')
        ->and(Carbon::parse($updated->start_date)->toDateString())->toBe('2034-01-01')
        ->and(Carbon::parse($updated->end_date)->toDateString())->toBe('2034-12-31');
});