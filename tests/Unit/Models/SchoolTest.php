<?php

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Models\ProcurementRequest;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

test('sekolah memiliki relasi satu ke pengaturan sekolah (SchoolSetting)', function () {
    $school = School::factory()->create();
    $setting = SchoolSetting::factory()->create(['school_id' => $school->id]);

    expect($school->setting)->toBeInstanceOf(SchoolSetting::class)
        ->and($school->setting->id)->toBe($setting->id);
});

test('sekolah memiliki relasi banyak ke pengguna (User)', function () {
    $school = School::factory()->create();
    User::factory()->count(3)->create(['school_id' => $school->id]);

    expect($school->users)->toHaveCount(3)
        ->and($school->users->first())->toBeInstanceOf(User::class);
});

test('sekolah memiliki relasi banyak ke permohonan pengadaan', function () {
    $school = School::factory()->create();
    ProcurementRequest::factory()->count(2)->create(['school_id' => $school->id]);

    expect($school->procurementRequests)->toHaveCount(2);
});

test('metode bisnis activate dan suspend dapat mengubah status aktif sekolah', function () {
    $school = School::factory()->create(['status' => 'active']);

    expect($school->isActive())->toBeTrue();

    $school->suspend();
    expect($school->isActive())->toBeFalse()
        ->and($school->status)->toBe('suspended');

    $school->activate();
    expect($school->isActive())->toBeTrue()
        ->and($school->status)->toBe('active');
});

test('menghitung total seluruh permohonan pengadaan dengan benar', function () {
    $school = School::factory()->create();
    ProcurementRequest::factory()->count(5)->create(['school_id' => $school->id]);

    expect($school->totalRequests())->toBe(5);
});

test('menghitung akumulasi nilai nominal pengadaan resmi (completed) dengan benar', function () {
    $school = School::factory()->create();

    $completedRequest = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => 'completed'
    ]);

    DB::table('procurement_request_items')->insert([
        [
            'procurement_request_id' => $completedRequest->id,
            'item_name' => 'Laptop Asus Core i5',
            'specification' => 'RAM 8GB, SSD 512GB',
            'unit' => 'unit',
            'quantity' => 1,
            'estimated_price' => 3000000.00,
            'official_price' => 3000000.00
        ],
        [
            'procurement_request_id' => $completedRequest->id,
            'item_name' => 'Printer Epson L3210',
            'specification' => 'Print, Scan, Copy',
            'unit' => 'unit',
            'quantity' => 1,
            'estimated_price' => 2000000.00,
            'official_price' => 2000000.00
        ],
    ]);

    $rejectedRequest = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => 'rejected'
    ]);

    DB::table('procurement_request_items')->insert([
        [
            'procurement_request_id' => $rejectedRequest->id,
            'item_name' => 'Proyektor BenQ',
            'specification' => '3000 Lumens SVGA',
            'unit' => 'unit',
            'quantity' => 1,
            'estimated_price' => 4000000.00,
            'official_price' => 4000000.00
        ]
    ]);

    expect($school->totalProcurementValue())->toBe(5000000.00);
});