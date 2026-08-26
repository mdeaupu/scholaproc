<?php

use App\Models\BudgetYear;
use App\Models\FundingSource;
use App\Models\PackageCategory;
use App\Models\User;
use App\Models\School;
use App\Models\ProcurementRequest;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('model user memiliki konfigurasi relasi yang benar', function () {
    $user = User::factory()->create();

    expect($user->school())->toBeInstanceOf(BelongsTo::class)
        ->and($user->histories())->toBeInstanceOf(HasMany::class)
        ->and($user->negotiations())->toBeInstanceOf(HasMany::class);
});

test('query scopes dapat memfilter user berdasarkan role masing-masing', function () {
    User::factory()->count(2)->create(['role' => 'superadmin']);
    User::factory()->count(3)->create(['role' => 'admin']);
    User::factory()->count(1)->create(['role' => 'school']);

    expect(User::superAdmin()->count())->toBe(2)
        ->and(User::admin()->count())->toBe(3)
        ->and(User::query()->school()->count())->toBe(1);
});

test('method otorisasi dasar mengembalikan kebenaran hak akses sesuai role', function () {
    $superAdmin = User::factory()->create(['role' => 'superadmin']);
    $admin = User::factory()->create(['role' => 'admin']);

    expect($superAdmin->isSuperAdmin())->toBeTrue()
        ->and($superAdmin->isAdmin())->toBeFalse()
        ->and($superAdmin->canManageSchools())->toBeTrue();

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->canManageSchools())->toBeFalse()
        ->and($admin->canProcessProcurement())->toBeTrue();
});

test('admin sekolah hanya berhak memutuskan negosiasi dan verifikasi barang milik sekolahnya sendiri', function () {
    $category = PackageCategory::factory()->create();
    $year = BudgetYear::factory()->create();
    $source = FundingSource::factory()->create();

    $sekolahA = School::factory()->create();
    $sekolahB = School::factory()->create();

    $adminSekolahA = User::factory()->create([
        'role' => 'school',
        'school_id' => $sekolahA->id
    ]);

    $requestSekolahA = ProcurementRequest::factory()->create([
        'school_id' => $sekolahA->id,
        'package_category_id' => $category->id,
        'budget_year_id' => $year->id,
        'funding_source_id' => $source->id,
    ]);

    $requestSekolahB = ProcurementRequest::factory()->create([
        'school_id' => $sekolahB->id,
        'package_category_id' => $category->id,
        'budget_year_id' => $year->id,
        'funding_source_id' => $source->id,
    ]);

    expect($adminSekolahA->canDecideNegotiation($requestSekolahA))->toBeTrue()
        ->and($adminSekolahA->canVerifyReceipt($requestSekolahA))->toBeTrue();

    expect($adminSekolahA->canDecideNegotiation($requestSekolahB))->toBeFalse()
        ->and($adminSekolahA->canVerifyReceipt($requestSekolahB))->toBeFalse();
});