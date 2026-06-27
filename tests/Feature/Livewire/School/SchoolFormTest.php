<?php

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Livewire\School\SchoolForm;
use Livewire\Livewire;
use function Pest\Laravel\{actingAs};

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('owner dapat mendaftarkan sekolah baru sekaligus data kop surat dan admin otomatis', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    actingAs($owner);

    Livewire::test(SchoolForm::class)
        ->set('npsn', '12345678')
        ->set('name', 'SMA Bina Bangsa')
        ->set('address', 'Jl. Merdeka No. 45')
        ->set('postal_code', '43251')
        ->set('phone_number', '0263998877')
        ->set('email', 'kontak@binabangsa.sch.id')
        ->set('status', 'active')
        ->set('kop_pusat', 'Yayasan Bina Bangsa Pusat')
        ->set('kop_provinsi', 'Regional Jawa Barat')
        ->set('kop_sub_wilayah', 'Cabang Cianjur')
        ->set('password', 'secret123')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    $this->assertDatabaseHas('schools', [
        'npsn' => '12345678',
        'name' => 'SMA Bina Bangsa'
    ]);

    $school = School::where('npsn', '12345678')->first();

    $this->assertDatabaseHas('school_settings', [
        'school_id' => $school->id,
        'kop_pusat' => 'Yayasan Bina Bangsa Pusat'
    ]);

    $this->assertDatabaseHas('users', [
        'school_id' => $school->id,
        'role' => 'admin_school',
        'username' => '12345678',
        'name' => 'Admin SMA Bina Bangsa'
    ]);

    $admin = User::where('school_id', $school->id)->where('role', 'admin_school')->first();
    expect(Hash::check('secret123', $admin->password))->toBeTrue();
});

test('aturan validasi form diterapkan dengan benar saat pendaftaran', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    actingAs($owner);

    Livewire::test(SchoolForm::class)
        ->set('npsn', 'tulisan-bukan-angka')
        ->set('name', '')
        ->set('password', 'short')
        ->call('save')
        ->assertHasErrors([
            'npsn' => 'numeric',
            'name' => 'required',
            'password' => 'min'
        ]);
});

test('password wajib diisi saat create', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    actingAs($owner);

    Livewire::test(SchoolForm::class)
        ->set('npsn', '12345678')
        ->set('name', 'SMA Test')
        ->set('address', 'Jl. Test')
        ->set('phone_number', '123')
        ->set('kop_pusat', 'Kop')
        ->set('kop_provinsi', 'Prov')
        ->set('password', '')
        ->call('save')
        ->assertHasErrors(['password' => 'required']);
});

test('owner dapat mengubah informasi sekolah dan kop surat yang sudah ada', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $school = School::factory()->create([
        'npsn' => '87654321',
        'name' => 'Nama Sekolah Lama'
    ]);

    $setting = SchoolSetting::factory()->create([
        'school_id' => $school->id,
        'kop_pusat' => 'Kop Pusat Lama'
    ]);

    $admin = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'admin_school',
        'username' => '87654321',
        'password' => Hash::make('oldpassword')
    ]);

    actingAs($owner);

    Livewire::test(SchoolForm::class, ['school' => $school])
        ->assertSet('name', 'Nama Sekolah Lama')
        ->assertSet('isEdit', true)
        ->set('name', 'Nama Sekolah Hasil Update')
        ->set('kop_pusat', 'Kop Pusat Hasil Update')
        ->set('password', '')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    expect($school->fresh()->name)->toBe('Nama Sekolah Hasil Update')
        ->and($school->setting->fresh()->kop_pusat)->toBe('Kop Pusat Hasil Update')
        ->and(Hash::check('oldpassword', $admin->fresh()->password))->toBeTrue();
});

test('update password mengubah password admin', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $school = School::factory()->create([
        'npsn' => '22222222',
        'name' => 'Sekolah B'
    ]);

    SchoolSetting::factory()->create(['school_id' => $school->id]);

    $admin = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'admin_school',
        'username' => '22222222',
        'password' => Hash::make('oldpass')
    ]);

    actingAs($owner);

    Livewire::test(SchoolForm::class, ['school' => $school])
        ->set('npsn', '22222222')
        ->set('name', 'Sekolah B')
        ->set('address', 'Jl. Baru')
        ->set('phone_number', '123')
        ->set('kop_pusat', 'Kop')
        ->set('kop_provinsi', 'Prov')
        ->set('password', 'newsecret123')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    expect(Hash::check('newsecret123', $admin->fresh()->password))->toBeTrue();
});

test('update tanpa password mempertahankan password lama', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $school = School::factory()->create([
        'npsn' => '33333333',
        'name' => 'Sekolah C'
    ]);

    SchoolSetting::factory()->create(['school_id' => $school->id]);

    $admin = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'admin_school',
        'username' => '33333333',
        'password' => Hash::make('oldpass')
    ]);

    actingAs($owner);

    Livewire::test(SchoolForm::class, ['school' => $school])
        ->set('npsn', '33333333')
        ->set('name', 'Nama Baru')
        ->set('address', 'Jl. Baru')
        ->set('phone_number', '123')
        ->set('kop_pusat', 'Kop')
        ->set('kop_provinsi', 'Prov')
        ->set('password', '')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    expect(Hash::check('oldpass', $admin->fresh()->password))->toBeTrue();
});

test('update sekolah yang tidak memiliki admin akan membuat admin jika password diisi', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $school = School::factory()->create([
        'npsn' => '44444444',
        'name' => 'Sekolah Tanpa Admin'
    ]);

    SchoolSetting::factory()->create(['school_id' => $school->id]);

    expect($school->admin)->toBeNull();

    actingAs($owner);

    Livewire::test(SchoolForm::class, ['school' => $school])
        ->set('npsn', '44444444')
        ->set('name', 'Sekolah Tanpa Admin')
        ->set('address', 'Jl. Baru')
        ->set('phone_number', '123')
        ->set('kop_pusat', 'Kop')
        ->set('kop_provinsi', 'Prov')
        ->set('password', 'newadmin123')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    $admin = $school->fresh()->admin;
    expect($admin)->not->toBeNull()
        ->and($admin->username)->toBe('44444444')
        ->and(Hash::check('newadmin123', $admin->password))->toBeTrue();
});

test('update sekolah tanpa admin dan password kosong tidak membuat admin', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $school = School::factory()->create([
        'npsn' => '55555555',
        'name' => 'Sekolah Tanpa Admin 2'
    ]);

    SchoolSetting::factory()->create(['school_id' => $school->id]);

    expect($school->admin)->toBeNull();

    actingAs($owner);

    Livewire::test(SchoolForm::class, ['school' => $school])
        ->set('npsn', '55555555')
        ->set('name', 'Sekolah Tanpa Admin 2')
        ->set('address', 'Jl. Baru')
        ->set('phone_number', '123')
        ->set('kop_pusat', 'Kop')
        ->set('kop_provinsi', 'Prov')
        ->set('password', '')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    expect($school->fresh()->admin)->toBeNull();
});

test('setiap sekolah hanya memiliki satu admin', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $school = School::factory()->create(['npsn' => '66666666']);
    SchoolSetting::factory()->create(['school_id' => $school->id]);

    User::factory()->create([
        'school_id' => $school->id,
        'role' => 'admin_school',
        'username' => '66666666'
    ]);

    $adminCount = User::where('school_id', $school->id)->where('role', 'admin_school')->count();
    expect($adminCount)->toBe(1);

    actingAs($owner);
    Livewire::test(SchoolForm::class, ['school' => $school])
        ->set('npsn', '66666666')
        ->set('name', 'Sekolah 6')
        ->set('address', 'Jl. 6')
        ->set('phone_number', '123')
        ->set('kop_pusat', 'Kop')
        ->set('kop_provinsi', 'Prov')
        ->set('password', '')
        ->call('save');

    $adminCountAfter = User::where('school_id', $school->id)->where('role', 'admin_school')->count();
    expect($adminCountAfter)->toBe(1);
});