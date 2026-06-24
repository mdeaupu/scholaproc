<?php

use App\Models\User;
use App\Models\School;
use App\Models\ProcurementRequest;
use App\Livewire\Dashboard\OwnerDashboard;
use App\Livewire\Dashboard\CvDashboard;
use App\Livewire\Dashboard\SchoolDashboard;
use Livewire\Livewire;

test('procurement request scopes return correct status data', function () {
    $school = School::factory()->create();

    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'submitted']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'verified']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'completed']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'rejected']);

    expect(ProcurementRequest::submitted()->count())->toBe(1)
        ->and(ProcurementRequest::verified()->count())->toBe(1)
        ->and(ProcurementRequest::completed()->count())->toBe(1)
        ->and(ProcurementRequest::rejected()->count())->toBe(1);
});

test('school models can count active and completed requests correctly', function () {
    $school = School::factory()->create();

    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'submitted']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'verified']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'completed']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'rejected']);

    expect($school->activeRequestsCount())->toBe(2) // submitted & verified
        ->and($school->completedRequestsCount())->toBe(1);
});

test('owner can access owner dashboard with correct financial stats view data', function () {
    $owner = User::factory()->create(['role' => 'owner']); // Menyesuaikan dengan state pabrikasi Anda
    $school = School::factory()->create(['status' => 'active']);

    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'submitted']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'completed']);

    Livewire::actingAs($owner)
        ->test(OwnerDashboard::class)
        ->assertStatus(200)
        ->assertViewHas('totalSchools', 1)
        ->assertViewHas('totalSubmittedRequests', 1)
        ->assertViewHas('totalCompletedRequests', 1)
        ->assertViewHas('grandTotalEstimated')
        ->assertViewHas('grandTotalOfficial');
});

test('admin cv can access cv dashboard with pending verifications data', function () {
    $adminCv = User::factory()->create(['role' => 'admin_cv']);
    $school = School::factory()->create();

    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => 'submitted'
    ]);

    Livewire::actingAs($adminCv)
        ->test(CvDashboard::class)
        ->assertStatus(200)
        ->assertViewHas('totalActiveProcesses', 1)
        ->assertViewHas('pendingVerifications', function ($pending) use ($request) {
            return $pending->contains($request) && $pending->first()->relationLoaded('school');
        });
});

test('admin school can access school dashboard with specific institutional view data', function () {
    $school = School::factory()->create(['name' => 'SMK Negeri 1 Kota']);

    $adminSchool = User::factory()->create([
        'role' => 'admin_school',
        'school_id' => $school->id
    ]);

    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'submitted']);

    Livewire::actingAs($adminSchool)
        ->test(SchoolDashboard::class)
        ->assertStatus(200)
        ->assertViewHas('schoolName', 'SMK Negeri 1 Kota')
        ->assertViewHas('activeCount', 1)
        ->assertViewHas('completedCount', 0)
        ->assertViewHas('recentRequests');
});

test('admin school sees fallback message if not bound to any school', function () {
    $unboundAdmin = User::factory()->create([
        'role' => 'admin_school',
        'school_id' => null
    ]);

    Livewire::actingAs($unboundAdmin)
        ->test(SchoolDashboard::class)
        ->assertStatus(200)
        ->assertViewHas('schoolName', 'Sekolah Belum Terpilih')
        ->assertViewHas('activeCount', 0)
        ->assertViewHas('completedCount', 0);
});