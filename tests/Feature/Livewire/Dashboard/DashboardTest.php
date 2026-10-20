<?php

use App\Livewire\Dashboard\AdminDashboard;
use App\Livewire\Dashboard\SchoolDashboard;
use App\Livewire\Dashboard\SuperAdminDashboard;
use App\Models\BudgetYear;
use App\Models\GeneratedDocument;
use App\Models\Notification;
use App\Models\ProcurementNegotiation;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Models\User;
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

test('active scope excludes draft completed rejected and cancelled requests', function () {
    $school = School::factory()->create();

    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'draft']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'submitted']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'processing']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'completed']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'rejected']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'cancelled']);

    expect(ProcurementRequest::active()->count())->toBe(2);
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

test('financial totals exclude rejected cancelled and soft deleted requests', function () {
    $school = School::factory()->create();

    $active = ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'submitted']);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $active->id,
        'quantity' => 2,
        'estimated_price' => 100000,
        'official_price' => 80000,
    ]);

    $rejected = ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'rejected']);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $rejected->id,
        'quantity' => 5,
        'estimated_price' => 100000,
        'official_price' => 90000,
    ]);

    $trashed = ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'submitted']);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $trashed->id,
        'quantity' => 3,
        'estimated_price' => 100000,
        'official_price' => 70000,
    ]);
    $trashed->delete();

    expect(ProcurementRequest::getTotalEstimatedAmount())->toBe((float) 200000)
        ->and(ProcurementRequest::getTotalOfficialAmount())->toBe((float) 160000);
});

test('request total estimated amount accessor sums item lines', function () {
    $school = School::factory()->create();
    $request = ProcurementRequest::factory()->create(['school_id' => $school->id]);

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'quantity' => 2,
        'estimated_price' => 150000,
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'quantity' => 1,
        'estimated_price' => 50000,
    ]);

    expect($request->fresh()->total_estimated_amount)->toBe((float) 350000);
});

test('status label and pill class follow prd vocabulary', function () {
    $request = ProcurementRequest::factory()->create(['status' => 'payment_pending']);

    expect($request->status_label)->toBe('Menunggu Pembayaran')
        ->and($request->status_pill_class)->not->toBeEmpty();

    $unknown = ProcurementRequest::factory()->create(['status' => 'unknown_status']);

    expect($unknown->status_label)->toBe('Unknown status');
});

test('superadmin can access owner dashboard with correct financial stats view data', function () {
    $owner = User::factory()->create(['role' => 'superadmin']);
    User::factory()->create(['role' => 'admin']);
    User::factory()->create(['role' => 'school', 'status' => 'inactive']);
    $school = School::factory()->create(['status' => 'active']);

    $request = ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'submitted']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'completed']);
    GeneratedDocument::create([
        'procurement_request_id' => $request->id,
        'document_type' => 'surat_pesanan',
        'file_path' => 'dummy/surat-pesanan.pdf',
        'download_token' => 'dummy-token',
        'generated_at' => now(),
    ]);

    activity('default')->causedBy($owner)->log('Menguji aktivitas superadmin');

    Livewire::actingAs($owner)
        ->test(SuperAdminDashboard::class)
        ->assertStatus(200)
        ->assertViewHas('totalSchools', 1)
        ->assertViewHas('totalActiveUsers', 2)
        ->assertViewHas('totalSubmittedRequests', 1)
        ->assertViewHas('totalCompletedRequests', 1)
        ->assertViewHas('totalRequests', 2)
        ->assertViewHas('totalDocuments', 1)
        ->assertViewHas('statusBreakdown', fn ($breakdown) => $breakdown['submitted'] === 1 && $breakdown['completed'] === 1)
        ->assertViewHas('recentActivity', fn ($activities) => $activities->count() === 1)
        ->assertViewHas('grandTotalEstimated')
        ->assertViewHas('grandTotalOfficial');
});

test('superadmin dashboard budget year filter narrows request stats and financial totals', function () {
    $owner = User::factory()->create(['role' => 'superadmin']);
    $yearA = BudgetYear::factory()->create();
    $yearB = BudgetYear::factory()->create([
        'name' => (string) (now()->year + 1),
        'start_date' => now()->addYear()->startOfYear(),
        'end_date' => now()->addYear()->endOfYear(),
    ]);
    $school = School::factory()->create();

    $requestA = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'budget_year_id' => $yearA->id,
        'status' => 'submitted',
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $requestA->id,
        'quantity' => 1,
        'estimated_price' => 100000,
        'official_price' => 90000,
    ]);

    $requestB = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'budget_year_id' => $yearB->id,
        'status' => 'submitted',
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $requestB->id,
        'quantity' => 1,
        'estimated_price' => 200000,
        'official_price' => 180000,
    ]);

    Livewire::actingAs($owner)
        ->test(SuperAdminDashboard::class)
        ->set('budgetYearId', $yearA->id)
        ->assertViewHas('totalRequests', 1)
        ->assertViewHas('totalSubmittedRequests', 1)
        ->assertViewHas('grandTotalEstimated', fn ($total) => (float) $total === 100000.0)
        ->assertViewHas('grandTotalOfficial', fn ($total) => (float) $total === 90000.0);
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
        ->assertViewHas('pendingVerificationCount', 1)
        ->assertViewHas('pendingNegotiationCount', 0)
        ->assertViewHas('pendingVerifications', function ($pending) use ($request) {
            return $pending->contains($request) && $pending->first()->relationLoaded('school');
        });
});

test('admin dashboard verification queue is limited while count stays complete', function () {
    $adminCv = User::factory()->create(['role' => 'admin']);
    $school = School::factory()->create();

    ProcurementRequest::factory()->count(12)->create([
        'school_id' => $school->id,
        'status' => 'submitted',
    ]);

    Livewire::actingAs($adminCv)
        ->test(AdminDashboard::class)
        ->assertViewHas('pendingVerifications', fn ($pending) => $pending->count() === 10)
        ->assertViewHas('pendingVerificationCount', 12);
});

test('admin dashboard counts negotiations awaiting cv response only', function () {
    $adminCv = User::factory()->create(['role' => 'admin']);
    $school = School::factory()->create();
    $staff = User::factory()->create(['role' => 'school', 'school_id' => $school->id]);

    $awaitingCv = ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'negotiation']);
    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $awaitingCv->id,
        'negotiation_status' => 'negotiating',
    ]);
    $negotiation = ProcurementNegotiation::create([
        'procurement_request_item_id' => $item->id,
        'round_number' => 1,
        'offered_by' => ProcurementNegotiation::OFFERED_BY_SCHOOL,
        'user_id' => $staff->id,
        'offered_price' => 90000,
        'status' => ProcurementNegotiation::STATUS_PENDING,
    ]);

    $awaitingSchool = ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'negotiation']);
    $itemFromCv = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $awaitingSchool->id,
        'negotiation_status' => 'negotiating',
    ]);
    ProcurementNegotiation::create([
        'procurement_request_item_id' => $itemFromCv->id,
        'round_number' => 1,
        'offered_by' => ProcurementNegotiation::OFFERED_BY_CV,
        'user_id' => $adminCv->id,
        'offered_price' => 120000,
        'status' => ProcurementNegotiation::STATUS_PENDING,
    ]);

    Livewire::actingAs($adminCv)
        ->test(AdminDashboard::class)
        ->assertViewHas('pendingNegotiationCount', 1)
        ->assertViewHas('pendingNegotiations', function ($pending) use ($negotiation) {
            return $pending->pluck('id')->contains($negotiation->id);
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
        ->assertViewHas('pendingNegotiationCount', 0)
        ->assertViewHas('paymentPendingCount', 0)
        ->assertViewHas('recentRequests');
});

test('school dashboard only shows data belonging to its own school', function () {
    $schoolA = School::factory()->create(['name' => 'SMK Negeri 1 Kota']);
    $schoolB = School::factory()->create(['name' => 'SMP Negeri 2 Kota']);
    $userA = User::factory()->create(['role' => 'school', 'school_id' => $schoolA->id]);

    $requestA = ProcurementRequest::factory()->create([
        'school_id' => $schoolA->id,
        'status' => 'submitted',
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $requestA->id,
        'quantity' => 2,
        'estimated_price' => 50000,
    ]);
    ProcurementRequest::factory()->create(['school_id' => $schoolB->id, 'status' => 'submitted']);

    GeneratedDocument::create([
        'procurement_request_id' => $requestA->id,
        'document_type' => 'surat_pesanan',
        'file_path' => 'dummy/a.pdf',
        'download_token' => 'token-a',
        'generated_at' => now(),
    ]);
    $requestB = ProcurementRequest::where('school_id', $schoolB->id)->first();
    GeneratedDocument::create([
        'procurement_request_id' => $requestB->id,
        'document_type' => 'surat_pesanan',
        'file_path' => 'dummy/b.pdf',
        'download_token' => 'token-b',
        'generated_at' => now(),
    ]);

    Notification::create([
        'procurement_request_id' => $requestA->id,
        'channel' => 'email',
        'recipient' => 'dummy-a@example.test',
        'message_content' => 'Notifikasi sekolah A',
        'status' => 'sent',
        'sent_at' => now(),
    ]);
    Notification::create([
        'procurement_request_id' => $requestB->id,
        'channel' => 'email',
        'recipient' => 'dummy-b@example.test',
        'message_content' => 'Notifikasi sekolah B',
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    Livewire::actingAs($userA)
        ->test(SchoolDashboard::class)
        ->assertViewHas('activeCount', 1)
        ->assertViewHas('recentRequests', function ($requests) use ($requestA) {
            return $requests->count() === 1 && $requests->first()->id === $requestA->id
                && $requests->first()->total_estimated_amount == 100000;
        })
        ->assertViewHas('recentDocuments', fn ($documents) => $documents->count() === 1)
        ->assertViewHas('recentNotifications', function ($notifications) {
            return $notifications->count() === 1
                && $notifications->first()->message_content === 'Notifikasi sekolah A';
        });
});

test('school dashboard counts negotiations awaiting its decision and payment follow ups', function () {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = User::factory()->create(['role' => 'school', 'school_id' => $school->id]);
    $cvStaff = User::factory()->create(['role' => 'admin']);

    $request = ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'negotiation']);
    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'negotiation_status' => 'negotiating',
    ]);
    ProcurementNegotiation::create([
        'procurement_request_item_id' => $item->id,
        'round_number' => 1,
        'offered_by' => ProcurementNegotiation::OFFERED_BY_CV,
        'user_id' => $cvStaff->id,
        'offered_price' => 120000,
        'status' => ProcurementNegotiation::STATUS_PENDING,
    ]);

    $otherRequest = ProcurementRequest::factory()->create([
        'school_id' => $otherSchool->id,
        'status' => 'negotiation',
    ]);
    $otherItem = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $otherRequest->id,
        'negotiation_status' => 'negotiating',
    ]);
    ProcurementNegotiation::create([
        'procurement_request_item_id' => $otherItem->id,
        'round_number' => 1,
        'offered_by' => ProcurementNegotiation::OFFERED_BY_CV,
        'user_id' => $cvStaff->id,
        'offered_price' => 150000,
        'status' => ProcurementNegotiation::STATUS_PENDING,
    ]);

    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'payment_pending']);
    ProcurementRequest::factory()->create(['school_id' => $school->id, 'status' => 'invoiced']);

    Livewire::actingAs($user)
        ->test(SchoolDashboard::class)
        ->assertViewHas('pendingNegotiationCount', 1)
        ->assertViewHas('paymentPendingCount', 2);
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
        ->assertViewHas('completedCount', 0)
        ->assertViewHas('pendingNegotiationCount', 0)
        ->assertViewHas('paymentPendingCount', 0)
        ->assertViewHas('recentRequests', fn ($requests) => $requests->isEmpty());
});
