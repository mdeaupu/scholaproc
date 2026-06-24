<?php

use App\Models\User;
use App\Models\School;
use App\Models\ProcurementRequest;
use Illuminate\Support\Facades\DB;
use function Pest\Laravel\{actingAs, get};

beforeEach(function () { });

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

    expect($school->activeRequestsCount())->toBe(2)
        ->and($school->completedRequestsCount())->toBe(1);
});

test('procurement request calculates global aggregates statistics correctly', function () {
    $school = School::factory()->create();
    $request = ProcurementRequest::factory()->create(['school_id' => $school->id]);

    DB::table('procurement_request_items')->insert([
        [
            'procurement_request_id' => $request->id,
            'line_number' => 1,
            'item_name' => 'Laptop Chromebook',
            'specification' => 'Intel Celeron, 4GB RAM, 64GB eMMC',
            'unit' => 'Unit',
            'quantity' => 2,
            'estimated_price' => 5000000,
            'official_price' => 4800000,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'procurement_request_id' => $request->id,
            'line_number' => 2,
            'item_name' => 'Proyektor EPSON',
            'specification' => 'XGA, 3600 Lumens',
            'unit' => 'Unit',
            'quantity' => 1,
            'estimated_price' => 7000000,
            'official_price' => 7200000,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    ]);

    expect(ProcurementRequest::getTotalEstimatedAmount())->toBe((float) 17000000)
        ->and(ProcurementRequest::getTotalOfficialAmount())->toBe((float) 16800000);
});

test('owner can access owner dashboard with correct financial stats view data', function () {
    $owner = User::factory()->owner()->create();
    $school = School::factory()->create(['status' => 'active']);

    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'submitted']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'completed']);

    $response = actingAs($owner)->get(route('dashboard.owner'));

    $response->assertStatus(200)
        ->assertViewIs('dashboard.owner')
        ->assertViewHasAll([
            'totalSchools' => 1,
            'totalSubmittedRequests' => 1,
            'totalCompletedRequests' => 1,
            'grandTotalEstimated',
            'grandTotalOfficial'
        ]);
});

test('admin cv can access cv dashboard with pending verification data', function () {
    $adminCv = User::factory()->adminCv()->create();
    $school = School::factory()->create();

    $request = ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'submitted']);

    $response = actingAs($adminCv)->get(route('dashboard.cv'));

    $response->assertStatus(200)
        ->assertViewIs('dashboard.cv')
        ->assertViewHas('pendingVerifications')
        ->assertViewHas('totalActiveProcesses', 1);

    $viewData = $response->original->getData()['pendingVerifications'];
    expect($viewData->first()->id)->toBe($request->id)
        ->and($viewData->first()->relationLoaded('school'))->toBeTrue();
});

test('admin school can access school dashboard with specific institutional view data', function () {
    $school = School::factory()->create(['name' => 'SMK Negeri 1 Kota']);

    $adminSchool = User::factory()->create([
        'role' => 'admin_school',
        'school_id' => $school->id
    ]);

    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'submitted']);

    $response = actingAs($adminSchool)->get(route('dashboard.school'));

    $response->assertStatus(200)
        ->assertViewIs('dashboard.school')
        ->assertViewHas([
            'schoolName' => 'SMK Negeri 1 Kota',
            'activeCount' => 1,
            'completedCount' => 0
        ])
        ->assertViewHas('recentRequests');
});

test('admin school sees fallback message if not bound to any school', function () {
    $unboundAdmin = User::factory()->create([
        'role' => 'admin_school',
        'school_id' => null
    ]);

    $response = actingAs($unboundAdmin)->get(route('dashboard.school'));

    $response->assertStatus(200)
        ->assertViewHas('schoolName', 'Sekolah Belum Terpilih')
        ->assertViewHas('activeCount', 0)
        ->assertViewHas('completedCount', 0);
});