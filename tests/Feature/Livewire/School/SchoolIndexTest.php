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
        ->assertSet('detailTab', 'pengguna')
        ->assertSee('Detail Sekolah')
        ->assertSee('Informasi Sekolah')
        ->assertSee('Kepala Sekolah')
        ->assertSee('Aksi Cepat')
        ->assertSee('Akun Admin Sekolah')
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
test('komponen menolak non-superadmin secara internal di setiap aksi', function () {
    $nonSuperadmin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($nonSuperadmin)
        ->test(SchoolIndex::class)
        ->assertStatus(403);
});

test('superadmin dapat mereset password akun admin sekolah', function () {
    $school = School::factory()->create();
    $account = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'school',
        'username' => $school->npsn,
        'password' => Hash::make('oldpass'),
    ]);
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->call('confirmResetPassword', $school->id)
        ->assertSet('confirmingResetPassword', true)
        ->assertSet('targetSchoolId', $school->id)
        ->call('resetUserPassword')
        ->assertHasErrors(['newPassword' => 'required'])
        ->set('newPassword', 'newsecret456')
        ->call('resetUserPassword')
        ->assertSet('confirmingResetPassword', false)
        ->assertSet('newPassword', '');

    expect(Hash::check('newsecret456', $account->fresh()->password))->toBeTrue();
});

test('reset password kurang dari delapan karakter ditolak', function () {
    $school = School::factory()->create();
    User::factory()->create([
        'school_id' => $school->id,
        'role' => 'school',
        'username' => $school->npsn,
        'password' => Hash::make('oldpass'),
    ]);
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->call('confirmResetPassword', $school->id)
        ->set('newPassword', 'short')
        ->call('resetUserPassword')
        ->assertHasErrors(['newPassword' => 'min'])
        ->assertSet('confirmingResetPassword', true);
});

test('superadmin dapat menonaktifkan lalu mengaktifkan kembali akun sekolah', function () {
    $school = School::factory()->create();
    $account = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'school',
        'username' => $school->npsn,
        'status' => 'active',
    ]);
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->call('confirmToggleAccount', $school->id)
        ->assertSet('confirmingToggleAccount', true)
        ->assertSet('targetAccountId', $account->id)
        ->call('toggleAccount')
        ->assertSet('confirmingToggleAccount', false);

    expect($account->fresh()->status)->toBe('suspended');

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->call('confirmToggleAccount', $school->id)
        ->call('toggleAccount');

    expect($account->fresh()->status)->toBe('active');
});

test('aksi akun gagal untuk sekolah yang belum memiliki akun admin', function () {
    $school = School::factory()->create();
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Livewire::actingAs($superadmin)
        ->test(SchoolIndex::class)
        ->call('confirmResetPassword', $school->id)
        ->assertStatus(404);
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
        ->assertSee('Total Akun Sekolah')
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
