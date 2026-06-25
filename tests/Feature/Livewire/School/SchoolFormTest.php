<?php

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Livewire\School\SchoolForm;
use Livewire\Livewire;
use function Pest\Laravel\{actingAs};

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('owner dapat mendaftarkan sekolah baru sekaligus data kop suratnya', function () {
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
});

test('aturan validasi form diterapkan dengan benar saat pendaftaran', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    actingAs($owner);

    Livewire::test(SchoolForm::class)
        ->set('npsn', 'tulisan-bukan-angka')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors([
            'npsn' => 'numeric',
            'name' => 'required'
        ]);
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

    actingAs($owner);

    Livewire::test(SchoolForm::class, ['school' => $school])
        ->assertSet('name', 'Nama Sekolah Lama')
        ->assertSet('isEdit', true)
        ->set('name', 'Nama Sekolah Hasil Update')
        ->set('kop_pusat', 'Kop Pusat Hasil Update')
        ->call('save')
        ->assertRedirect(route('schools.index'));

    expect($school->fresh()->name)->toBe('Nama Sekolah Hasil Update')
        ->and($school->setting->fresh()->kop_pusat)->toBe('Kop Pusat Hasil Update');
});