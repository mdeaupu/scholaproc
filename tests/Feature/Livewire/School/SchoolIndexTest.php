<?php

use App\Livewire\School\SchoolIndex;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use function Pest\Laravel\{actingAs, get, assertSoftDeleted};

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('pengguna bukan owner dilarang keras mengakses manajemen sekolah', function () {

    $nonOwner = User::factory()->create(['role' => 'admin_school']);
    actingAs($nonOwner)
        ->get(route('schools.index'))
        ->assertStatus(403);
});

test('owner dapat membuka manajemen sekolah dan melihat daftar instansi', function () {
    $school = School::factory()->create(['name' => 'SMKN 1 Cianjur']);
    $owner = User::factory()->create(['role' => 'owner']);

    actingAs($owner);

    Livewire::test(SchoolIndex::class)
        ->assertSee($school->name)
        ->assertSee($school->npsn);
});

test('owner dapat mencari sekolah berdasarkan nama ataupun npsn', function () {
    $school1 = School::factory()->create(['name' => 'SMAN 1 Cianjur', 'npsn' => '10101010']);
    $school2 = School::factory()->create(['name' => 'SMKN 2 Bandung', 'npsn' => '20202020']);
    $owner = User::factory()->create(['role' => 'owner']);

    actingAs($owner);

    Livewire::test(SchoolIndex::class)
        ->set('search', 'Cianjur')
        ->assertSee($school1->name)
        ->assertDontSee($school2->name)
        ->set('search', '20202020')
        ->assertSee($school2->name)
        ->assertDontSee($school1->name);
});

test('owner dapat memicu kemunculan modal detail statistik sekolah', function () {
    $school = School::factory()->create();
    $owner = User::factory()->create(['role' => 'owner']);

    actingAs($owner);

    Livewire::test(SchoolIndex::class)
        ->call('show', $school->id)
        ->assertSet('detailModal', true)
        ->assertSet('selectedSchool.id', $school->id);
});

test('owner dapat mengaktifkan kembali sekolah yang sedang dibekukan', function () {
    $school = School::factory()->create(['status' => 'suspended']);
    $owner = User::factory()->create(['role' => 'owner']);

    actingAs($owner);

    Livewire::test(SchoolIndex::class)
        ->call('activate', $school->id);

    expect($school->fresh()->isActive())->toBeTrue();
});

test('owner dapat membekukan sekolah aktif melalui alur konfirmasi modal', function () {
    $school = School::factory()->create(['status' => 'active', 'name' => 'Sekolah Target Beku']);
    $owner = User::factory()->create(['role' => 'owner']);

    actingAs($owner);

    Livewire::test(SchoolIndex::class)
        ->call('confirmSuspend', $school->id, $school->name)
        ->assertSet('targetSchoolId', $school->id)
        ->assertSet('confirmingSuspend', true)
        ->call('suspend')
        ->assertSet('confirmingSuspend', false);

    expect($school->fresh()->status)->toBe('suspended');
});

test('owner dapat menghapus sekolah melalui alur konfirmasi modal (Soft Delete)', function () {
    $school = School::factory()->create(['name' => 'Sekolah Dihapus']);
    $owner = User::factory()->create(['role' => 'owner']);

    actingAs($owner);

    Livewire::test(SchoolIndex::class)
        ->call('confirmDestroy', $school->id, $school->name)
        ->assertSet('confirmingDestroy', true)
        ->call('destroy')
        ->assertSet('confirmingDestroy', false);

    assertSoftDeleted($school);
});