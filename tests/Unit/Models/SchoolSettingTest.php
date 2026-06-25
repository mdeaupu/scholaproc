<?php

use App\Models\School;
use App\Models\SchoolSetting;
use Tests\TestCase;

uses(TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

test('pengaturan sekolah terhubung balik ke model School', function () {
    $school = School::factory()->create();
    $setting = SchoolSetting::factory()->create(['school_id' => $school->id]);

    expect($setting->school)->toBeInstanceOf(School::class)
        ->and($setting->school->id)->toBe($school->id);
});

test('metode getLetterHead menghasilkan struktur array kop surat berhuruf kapital', function () {
    $school = School::factory()->create([
        'name' => 'SMAN 1 Cianjur',
        'address' => 'Jl. Siliwangi No. 1',
        'postal_code' => '43211',
        'phone_number' => '026312345',
        'email' => 'info@sman1cj.sch.id'
    ]);

    $setting = SchoolSetting::factory()->create([
        'school_id' => $school->id,
        'kop_pusat' => 'Pemerintah Provinsi Jawa Barat',
        'kop_provinsi' => 'Dinas Pendidikan Wilayah VI',
        'kop_sub_wilayah' => 'Kabupaten Cianjur'
    ]);

    $letterHead = $setting->getLetterHead();

    expect($letterHead['pusat'])->toBe('PEMERINTAH PROVINSI JAWA BARAT')
        ->and($letterHead['provinsi'])->toBe('DINAS PENDIDIKAN WILAYAH VI')
        ->and($letterHead['sub_wilayah'])->toBe('KABUPATEN CIANJUR')
        ->and($letterHead['sekolah'])->toBe('SMAN 1 CIANJUR')
        ->and($letterHead['alamat_lengkap'])->toBe('Jl. Siliwangi No. 1 Kode Pos: 43211')
        ->and($letterHead['kontak'])->toBe('Telp: 026312345 | Email: info@sman1cj.sch.id');
});