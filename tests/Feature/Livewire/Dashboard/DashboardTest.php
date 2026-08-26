<?php

use App\Livewire\Dashboard\AdminDashboard;
use App\Livewire\Dashboard\SuperAdminDashboard;
use App\Models\User;
use App\Models\School;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Livewire\Dashboard\SchoolDashboard;
use Livewire\Livewire;
use Illuminate\Support\Carbon;

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

test('scope awaiting negotiation correctly filters requests based on item status', function () {
    $school = School::factory()->create();

    $requestNegotiating = ProcurementRequest::factory()->create(['school_id' => $school->id]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $requestNegotiating->id,
        'negotiation_status' => 'negotiating'
    ]);

    $requestAccepted = ProcurementRequest::factory()->create(['school_id' => $school->id]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $requestAccepted->id,
        'negotiation_status' => 'accepted'
    ]);

    $awaitingRequests = ProcurementRequest::awaitingNegotiation()->get();

    expect($awaitingRequests->count())->toBe(1)
        ->and($awaitingRequests->first()->id)->toBe($requestNegotiating->id);
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

test('calculates total estimated and official amounts correctly based on live and locked snapshot', function () {
    $school = School::factory()->create();

    $unlockedRequest = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'totals_locked_at' => null,
        'grand_total' => null,
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $unlockedRequest->id,
        'quantity' => 2,
        'estimated_price' => 100000,
        'official_price' => 90000,
    ]);

    $lockedRequest = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'totals_locked_at' => Carbon::now(),
        'grand_total' => 150000,
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $lockedRequest->id,
        'quantity' => 1,
        'estimated_price' => 200000,
        'official_price' => 180000,
    ]);

    expect(ProcurementRequest::getTotalEstimatedAmount())->toBe((float) 400000);

    expect(ProcurementRequest::getTotalOfficialAmount())->toBe((float) 330000);
});

test('superadmin can access owner dashboard with correct financial stats view data', function () {
    $owner = User::factory()->create(['role' => 'superadmin']);
    $school = School::factory()->create(['status' => 'active']);

    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'submitted']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'completed']);

    Livewire::actingAs($owner)
        ->test(SuperAdminDashboard::class)
        ->assertStatus(200)
        ->assertViewHas('totalSchools', 1)
        ->assertViewHas('totalSubmittedRequests', 1)
        ->assertViewHas('totalCompletedRequests', 1)
        ->assertViewHas('grandTotalEstimated')
        ->assertViewHas('grandTotalOfficial');
});

test('admin cv can access cv dashboard with pending verifications data', function () {
    $adminCv = User::factory()->create(['role' => 'admin']);
    $school = School::factory()->create();

    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => 'submitted'
    ]);

    Livewire::actingAs($adminCv)
        ->test(AdminDashboard::class)
        ->assertStatus(200)
        ->assertViewHas('totalActiveProcesses', 1)
        ->assertViewHas('pendingVerifications', function ($pending) use ($request) {
            return $pending->contains($request) && $pending->first()->relationLoaded('school');
        });
});

test('admin school can access school dashboard with specific institutional view data', function () {
    $school = School::factory()->create(['name' => 'SMK Negeri 1 Kota']);

    $adminSchool = User::factory()->create([
        'role' => 'school',
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
        'role' => 'school',
        'school_id' => null
    ]);

    Livewire::actingAs($unboundAdmin)
        ->test(SchoolDashboard::class)
        ->assertStatus(200)
        ->assertViewHas('schoolName', 'Sekolah Belum Terpilih')
        ->assertViewHas('activeCount', 0)
        ->assertViewHas('completedCount', 0);
});