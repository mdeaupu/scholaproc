<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

test('gates authorize correct roles exclusively', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $adminCv = User::factory()->create(['role' => 'admin_cv']);
    $adminSchool = User::factory()->create(['role' => 'admin_school']);

    expect(Gate::forUser($owner)->allows('owner-only'))->toBeTrue()
        ->and(Gate::forUser($adminCv)->allows('owner-only'))->toBeFalse();

    expect(Gate::forUser($adminCv)->allows('admin-cv-only'))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('admin-cv-only'))->toBeFalse();

    expect(Gate::forUser($adminSchool)->allows('admin-school-only'))->toBeTrue()
        ->and(Gate::forUser($adminCv)->allows('admin-school-only'))->toBeFalse();
});