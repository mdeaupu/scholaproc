<?php

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Models\ProcurementRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('sekolah memiliki relasi satu ke pengaturan sekolah (SchoolSetting)', function () {
    $school = School::factory()->create();
    $setting = SchoolSetting::factory()->create(['school_id' => $school->id]);

    expect($school->setting)->toBeInstanceOf(SchoolSetting::class)
        ->and($school->setting->id)->toBe($setting->id);
});

test('sekolah memiliki relasi satu ke akun pengguna (User)', function () {
    $school = School::factory()->create();

    $user = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'school'
    ]);

    expect($school->account)->toBeInstanceOf(User::class)
        ->and($school->account->id)->toBe($user->id);
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

    ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => 'completed',
        'grand_total' => 5000000.00
    ]);

    ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => 'rejected',
        'grand_total' => 4000000.00
    ]);

    expect($school->totalProcurementValue())->toBe(5000000.00);
});