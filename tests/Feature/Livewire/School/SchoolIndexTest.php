<?php

use App\Livewire\School\SchoolIndex;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertSoftDeleted;

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
        ->assertSee($school->npsn)
        ->assertSee('badge-sm font-semibold', false)
        ->assertDontSee('label="Aktif"', false);
});

test('superadmin dapat membuka modal detail sekolah', function () {
    $school = School::factory()->create([
        'name' => 'SMAN 1 Cianjur',
        'address' => 'Jl. Merdeka No. 1, Cianjur',
    ]);
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->call('show', $school->id)
        ->assertSet('detailModal', true)
        ->assertSee('Detail Sekolah')
        ->assertSee('Informasi Sekolah')
        ->assertSee('Kepala Sekolah')
        ->assertSee('Akun Admin Sekolah')
        ->assertSee('Sumber Dana Boleh')
        ->assertSee('Kop Surat')
        ->assertDontSee('Aksi Cepat')
        ->assertSee('Total Pengajuan')
        ->assertSee('Jl. Merdeka No. 1, Cianjur')
        ->assertSee($school->name);
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

test('superadmin dapat menghapus sekolah melalui alur konfirmasi modal (Soft Delete)', function () {
    $school = School::factory()->create(['name' => 'Sekolah Dihapus']);
    $account = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'school',
        'username' => $school->npsn,
        'status' => 'active',
    ]);
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->call('confirmDestroy', $school->id, $school->name)
        ->assertSet('confirmingDestroy', true)
        ->call('destroy')
        ->assertSet('confirmingDestroy', false);

    assertSoftDeleted($school);
    expect($account->fresh()->status)->toBe('suspended');
});

test('menu aksi sekolah hanya menyediakan detail, edit data, dan hapus', function () {
    $school = School::factory()->create();
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->assertSee('Hapus (Soft Delete)')
        ->assertDontSee('Aktivasi / Suspend')
        ->assertDontSee('Reset Password Akun')
        ->assertDontSee('Nonaktifkan Akun')
        ->assertDontSee('Aktifkan Akun');
});

test('komponen menolak non-superadmin secara internal di setiap aksi', function () {
    $nonSuperadmin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($nonSuperadmin)
        ->test(SchoolIndex::class)
        ->assertStatus(403);
});

test('halaman menampilkan ringkasan statistik sekolah', function () {
    School::factory()->count(2)->create(['status' => 'active']);
    School::factory()->create(['status' => 'suspended']);
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->assertSee('Total Sekolah')
        ->assertSee('Sekolah Aktif')
        ->assertSee('Sekolah Nonaktif')
        ->assertDontSee('Total Akun Sekolah')
        ->assertSee('66,7% dari total');
});

test('superadmin dapat mencari sekolah berdasarkan alamat', function () {
    $school1 = School::factory()->create([
        'name' => 'SMAN 1 Cianjur',
        'npsn' => '10101010',
        'address' => 'Jl. Melati No. 5, Bandung',
    ]);
    $school2 = School::factory()->create([
        'name' => 'SMKN 2 Bandung',
        'npsn' => '20202020',
        'address' => 'Jl. Kenanga No. 8, Surabaya',
    ]);
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->set('search', 'Melati')
        ->assertSee($school1->name)
        ->assertDontSee($school2->name);
});

test('filter status hanya menampilkan sekolah dengan status terpilih', function () {
    $activeSchool = School::factory()->create(['name' => 'SMAN 1 Jakarta', 'status' => 'active']);
    $suspendedSchool = School::factory()->create(['name' => 'SMAN 2 Bandung', 'status' => 'suspended']);
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->set('filterStatus', 'suspended')
        ->assertSee($suspendedSchool->name)
        ->assertDontSee($activeSchool->name);
});

test('superadmin dapat mengurutkan tabel dan nama kolom tidak dikenal jatuh ke urutan aman', function () {
    School::factory()->create(['name' => 'SMAN 1 Cianjur', 'npsn' => '10101010']);
    School::factory()->create(['name' => 'SMKN 2 Bandung', 'npsn' => '20202020']);
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $component = Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->set('sortBy', ['column' => 'name', 'direction' => 'asc']);

    expect($component->viewData('schools')->pluck('name')->all())
        ->toBe(['SMAN 1 Cianjur', 'SMKN 2 Bandung']);

    $component
        ->set('sortBy', ['column' => 'name; drop table schools', 'direction' => 'asc'])
        ->assertSee('SMAN 1 Cianjur');
});
