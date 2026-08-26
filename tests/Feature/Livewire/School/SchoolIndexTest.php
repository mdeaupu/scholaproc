<?php

use App\Livewire\School\SchoolIndex;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use function Pest\Laravel\{actingAs, get, assertSoftDeleted};

uses(RefreshDatabase::class);

test('pengguna bukan superadmin dilarang keras mengakses manajemen sekolah', function () {
    $nonSuperadmin = User::factory()->create(['role' => 'admin']);

    actingAs($nonSuperadmin)
        ->get(route('schools.index'))
        ->assertStatus(403);
});

test('superadmin dapat membuka manajemen sekolah dan melihat daftar instansi', function () {
    $school = School::factory()->create(['name' => 'SMKN 1 Cianjur']);
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->assertSee($school->name)
        ->assertSee($school->npsn);
});

test('superadmin dapat mencari sekolah berdasarkan nama ataupun npsn', function () {
    $school1 = School::factory()->create(['name' => 'SMAN 1 Cianjur', 'npsn' => '10101010']);
    $school2 = School::factory()->create(['name' => 'SMKN 2 Bandung', 'npsn' => '20202020']);
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->set('search', 'Cianjur')
        ->assertSee($school1->name)
        ->assertDontSee($school2->name)
        ->set('search', '20202020')
        ->assertSee($school2->name)
        ->assertDontSee($school1->name);
});

test('superadmin dapat memicu kemunculan modal detail statistik sekolah', function () {
    $school = School::factory()->create();
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->call('show', $school->id)
        ->assertSet('detailModal', true)
        ->assertSet('selectedSchool.id', $school->id);
});

test('superadmin dapat mengaktifkan kembali sekolah yang sedang dibekukan', function () {
    $school = School::factory()->create(['status' => 'suspended']);
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->call('activate', $school->id);

    expect($school->fresh()->isActive())->toBeTrue();
});

test('superadmin dapat membekukan sekolah aktif melalui alur konfirmasi modal', function () {
    $school = School::factory()->create(['status' => 'active', 'name' => 'Sekolah Target Beku']);
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->call('confirmSuspend', $school->id, $school->name)
        ->assertSet('targetSchoolId', $school->id)
        ->assertSet('confirmingSuspend', true)
        ->call('suspend')
        ->assertSet('confirmingSuspend', false);

    expect($school->fresh()->status)->toBe('suspended');
});

test('superadmin dapat menghapus sekolah melalui alur konfirmasi modal (Soft Delete)', function () {
    $school = School::factory()->create(['name' => 'Sekolah Dihapus']);
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->call('confirmDestroy', $school->id, $school->name)
        ->assertSet('confirmingDestroy', true)
        ->call('destroy')
        ->assertSet('confirmingDestroy', false);

    assertSoftDeleted($school);
});