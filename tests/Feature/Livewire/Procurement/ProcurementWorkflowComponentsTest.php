<?php

use App\Livewire\Procurement\ProcurementRequestDetail;
use App\Livewire\Procurement\ProcurementReviewPanel;
use App\Models\BudgetYear;
use App\Models\FundingSource;
use App\Models\ItemUnit;
use App\Models\PackageCategory;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

test('school can view own request details with item and actual status history', function () {
    $school = School::factory()->create([
        'name' => 'SMP Negeri Contoh',
        'npsn' => '12345678',
    ]);
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $this->actingAs($user);

    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'item_name' => 'Kertas HVS',
        'quantity' => 3,
        'estimated_price' => 25000,
    ]);
    $request->recordHistory($user, ProcurementRequest::STATUS_DRAFT, 'Draf pengajuan dibuat.');

    Livewire::test(ProcurementRequestDetail::class, ['procurementRequest' => $request])
        ->assertStatus(200)
        ->assertSee('Detail Pengajuan Barang')
        ->assertSee('PGN-'.$request->created_at->format('Y').'-'.str_pad((string) $request->id, 4, '0', STR_PAD_LEFT))
        ->assertSee('SMP Negeri Contoh')
        ->assertSee('Kertas HVS')
        ->assertSee('Rp 75.000')
        ->assertSee('Draf pengajuan dibuat.')
        ->assertSee('Kirim Pengajuan')
        ->assertSee('Hapus Pengajuan')
        ->assertDontSee('Review Pengajuan')
        ->assertDontSee('Selesaikan Pengadaan');
});

test('school cannot view another school request', function () {
    $school = School::factory()->create();
    $anotherSchool = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $request = ProcurementRequest::factory()->create(['school_id' => $anotherSchool->id]);

    $this->actingAs($user);

    expect(fn () => app(ProcurementRequestDetail::class)->mount($request))
        ->toThrow(HttpException::class);
});

test('internal staff sees the review panel embedded in request details', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $request = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);

    Livewire::test(ProcurementRequestDetail::class, ['procurementRequest' => $request])
        ->assertSee('Review Pengajuan')
        ->assertSee('Teruskan ke Negosiasi');
});

test('school can submit its own draft from the detail page', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $this->actingAs($user);

    $category = PackageCategory::factory()->create(['is_active' => true]);
    $year = BudgetYear::factory()->create(['is_active' => true]);
    $source = FundingSource::factory()->create(['is_active' => true]);
    $school->fundingSources()->attach($source);
    $unit = ItemUnit::factory()->create(['is_active' => true]);
    $request = ProcurementRequest::factory()->create([
        'school_id' => $user->school_id,
        'package_category_id' => $category->id,
        'budget_year_id' => $year->id,
        'funding_source_id' => $source->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'unit_id' => $unit->id,
    ]);

    Livewire::test(ProcurementRequestDetail::class, ['procurementRequest' => $request])
        ->call('submitRequest')
        ->assertHasNoErrors()
        ->assertSee('Menunggu Verifikasi');

    expect($request->refresh()->status)->toBe(ProcurementRequest::STATUS_SUBMITTED);

    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $request->id,
        'user_id' => $user->id,
        'status' => ProcurementRequest::STATUS_SUBMITTED,
    ]);
});

test('school can soft delete its own draft from detail and preserve its history', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);
    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
    ]);
    $request->recordHistory($user, ProcurementRequest::STATUS_DRAFT, 'Draf tetap tercatat.');

    Livewire::actingAs($user)
        ->test(ProcurementRequestDetail::class, ['procurementRequest' => $request])
        ->call('confirmDeleteDraft')
        ->assertSet('confirmingDeleteDraft', true)
        ->assertSee('Ya, Hapus')
        ->call('deleteDraft')
        ->assertRedirect(route('procurement.index'));

    $this->assertSoftDeleted('procurement_requests', ['id' => $request->id]);
    $this->assertSoftDeleted('procurement_request_items', ['id' => $item->id]);
    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $request->id,
        'notes' => 'Draf tetap tercatat.',
    ]);
});

test('school cannot delete a request that is not a draft from detail', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_NEEDS_REVISION,
    ]);
    $component = app(ProcurementRequestDetail::class);
    $component->procurementRequest = $request;
    $this->actingAs($user);

    expect(fn () => $component->deleteDraft())
        ->toThrow(HttpException::class);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_NEEDS_REVISION);
});

test('school cannot submit a request with inactive item unit from the detail page', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $this->actingAs($user);

    $category = PackageCategory::factory()->create(['is_active' => true]);
    $year = BudgetYear::factory()->create(['is_active' => true]);
    $source = FundingSource::factory()->create(['is_active' => true]);
    $school->fundingSources()->attach($source);
    $inactiveUnit = ItemUnit::factory()->create(['is_active' => false]);
    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'package_category_id' => $category->id,
        'budget_year_id' => $year->id,
        'funding_source_id' => $source->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'unit_id' => $inactiveUnit->id,
    ]);

    Livewire::test(ProcurementRequestDetail::class, ['procurementRequest' => $request])
        ->call('submitRequest');

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_DRAFT);
    $this->assertDatabaseMissing('procurement_request_histories', [
        'procurement_request_id' => $request->id,
        'status' => ProcurementRequest::STATUS_SUBMITTED,
    ]);
});

test('admin can request a revision with a recorded reason', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $request = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_SUBMITTED]);

    Livewire::test(ProcurementReviewPanel::class, ['procurementRequest' => $request])
        ->set('revisionReason', 'Lengkapi rincian spesifikasi barang.')
        ->call('requestRevision')
        ->assertHasNoErrors()
        ->assertSet('revisionModal', false)
        ->assertSet('revisionReason', '');

    expect($request->refresh()->status)->toBe(ProcurementRequest::STATUS_NEEDS_REVISION);
    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $request->id,
        'user_id' => $admin->id,
        'status' => ProcurementRequest::STATUS_NEEDS_REVISION,
        'notes' => 'Revisi diminta: Lengkapi rincian spesifikasi barang.',
    ]);
});

test('revision reason is required and submitted request remains unchanged', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $request = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_SUBMITTED]);

    Livewire::test(ProcurementReviewPanel::class, ['procurementRequest' => $request])
        ->set('revisionReason', '')
        ->call('requestRevision')
        ->assertHasErrors(['revisionReason' => 'required']);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_SUBMITTED);
});

test('school cannot call internal review actions', function () {
    $user = User::factory()->school()->create();
    $this->actingAs($user);

    $request = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_SUBMITTED]);

    $component = app(ProcurementReviewPanel::class);
    $component->procurementRequest = $request;

    expect(fn () => $component->verify())
        ->toThrow(HttpException::class);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_SUBMITTED);
});

test('admin can verify a submitted request with actor and timestamp recorded', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $request = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_SUBMITTED]);

    Livewire::test(ProcurementReviewPanel::class, ['procurementRequest' => $request])
        ->call('verify')
        ->assertHasNoErrors()
        ->assertSee('Terverifikasi');

    $verifiedRequest = $request->fresh();

    expect($verifiedRequest->status)->toBe(ProcurementRequest::STATUS_VERIFIED)
        ->and($verifiedRequest->reviewed_at)->not->toBeNull()
        ->and($verifiedRequest->reviewed_by)->toBe($admin->id)
        ->and($verifiedRequest->verified_at)->toBeNull()
        ->and($verifiedRequest->verified_by)->toBeNull();
});

test('admin can continue a verified request to negotiation from the review panel', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $request = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);

    Livewire::test(ProcurementReviewPanel::class, ['procurementRequest' => $request])
        ->assertSee('Teruskan ke Negosiasi')
        ->call('startNegotiation')
        ->assertHasNoErrors()
        ->assertSee('Negosiasi Harga');

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_NEGOTIATION);
    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $request->id,
        'user_id' => $admin->id,
        'status' => ProcurementRequest::STATUS_NEGOTIATION,
    ]);
});

test('non-internal users cannot access the review panel', function () {
    $user = User::factory()->school()->create();
    $this->actingAs($user);
    $request = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_VERIFIED]);

    expect(fn () => app(ProcurementReviewPanel::class)->mount($request))
        ->toThrow(HttpException::class);
});

test('admin can reject a submitted request with a recorded reason', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $request = ProcurementRequest::factory()->create(['status' => ProcurementRequest::STATUS_SUBMITTED]);

    Livewire::test(ProcurementReviewPanel::class, ['procurementRequest' => $request])
        ->set('rejectReason', 'Dokumen pendukung tidak lengkap.')
        ->call('reject')
        ->assertHasNoErrors()
        ->assertSet('rejectModal', false);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_REJECTED);
    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $request->id,
        'user_id' => $admin->id,
        'status' => ProcurementRequest::STATUS_REJECTED,
        'notes' => 'Ditolak: Dokumen pendukung tidak lengkap.',
    ]);
});
