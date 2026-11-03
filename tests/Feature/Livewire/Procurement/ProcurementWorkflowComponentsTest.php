<?php

use App\Livewire\Procurement\ProcurementRequestDetail;
use App\Livewire\Procurement\ProcurementProcessPanel;
use App\Livewire\Procurement\ProcurementReviewPanel;
use App\Livewire\Procurement\NegotiationPanel;
use App\Models\BudgetYear;
use App\Models\FundingSource;
use App\Models\ItemUnit;
use App\Models\PackageCategory;
use App\Models\ProcurementNegotiation;
use App\Models\ProcurementRequest;
use App\Models\Subsidiary;
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
        ->assertSee('Detail Pengajuan Pengadaan Barang')
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

test('procurement detail tabs switch visible content and default to negotiation when active', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);
    ProcurementRequestItem::factory()->create(['procurement_request_id' => $request->id]);
    $this->actingAs($user);

    Livewire::test(ProcurementRequestDetail::class, ['procurementRequest' => $request])
        ->assertSet('activeTab', 'informasi')
        ->assertSee('Informasi & Barang')
        ->assertSee('Detail Barang')
        ->call('setActiveTab', 'dokumen')
        ->assertSet('activeTab', 'dokumen')
        ->assertSee('Dokumen Pengadaan')
        ->call('setActiveTab', 'negosiasi')
        ->assertSet('activeTab', 'negosiasi')
        ->assertSee('Ruang Negosiasi')
        ->assertDontSee('Detail Barang');

    $negotiatingRequest = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    ProcurementRequestItem::factory()->create(['procurement_request_id' => $negotiatingRequest->id]);
    $admin = User::factory()->admin()->create();
    $negotiatingRequest->assignSubsidiary(Subsidiary::factory()->create(), $admin);
    $negotiatingRequest->startNegotiation($admin);

    Livewire::test(ProcurementRequestDetail::class, ['procurementRequest' => $negotiatingRequest])
        ->assertSet('activeTab', 'negosiasi')
        ->assertSee('Negosiasi Harga Per Item');
});

test('procurement process panel switches content through its navigation tabs', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $request = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'item_name' => 'Laptop untuk tab negosiasi',
    ]);
    $request->assignSubsidiary(Subsidiary::factory()->create(), $admin);
    $request->startNegotiation($admin);

    Livewire::test(ProcurementProcessPanel::class, ['procurementRequest' => $request])
        ->assertSet('activeTab', 'negosiasi')
        ->assertSee('Negosiasi Harga Per Item')
        ->call('setActiveTab', 'informasi')
        ->assertSet('activeTab', 'informasi')
        ->assertSee('Informasi & Barang')
        ->assertSee('Laptop untuk tab negosiasi')
        ->call('setActiveTab', 'negosiasi')
        ->assertSet('activeTab', 'negosiasi')
        ->assertSee('Negosiasi Harga Per Item')
        ->call('setActiveTab', 'pengiriman')
        ->assertSet('activeTab', 'pengiriman')
        ->assertSee('Pemrosesan Pesanan')
        ->call('setActiveTab', 'dokumen')
        ->assertSet('activeTab', 'dokumen')
        ->assertSee('Dokumen Terbit');
});

test('negotiation panel only renders rounds belonging to its request', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $request = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
    ]);
    $request->assignSubsidiary(Subsidiary::factory()->create(), $admin);
    $request->startNegotiation($admin);

    $otherRequest = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    $otherItem = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $otherRequest->id,
    ]);
    $otherRequest->assignSubsidiary(Subsidiary::factory()->create(), $admin);
    $otherRequest->startNegotiation($admin);
    ProcurementNegotiation::offer(
        $otherItem,
        $admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        123456,
        'Catatan transaksi lain'
    );

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $request])
        ->set('selectedItemId', $otherItem->id)
        ->assertDontSee('Catatan transaksi lain')
        ->assertDontSee('123.456');
});

test('internal staff sees the review panel embedded in request details', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $request = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    $subsidiary = Subsidiary::factory()->create([
        'name' => 'Entitas Aktif untuk Review',
        'npwp' => '12345678901',
    ]);
    Subsidiary::factory()->create([
        'name' => 'Entitas Nonaktif untuk Review',
        'status' => Subsidiary::STATUS_INACTIVE,
    ]);
    $request->assignSubsidiary($subsidiary, $admin);

    Livewire::test(ProcurementRequestDetail::class, ['procurementRequest' => $request])
        ->assertSee('Review Pengajuan')
        ->assertSee('Teruskan ke Negosiasi');

    Livewire::test(ProcurementReviewPanel::class, ['procurementRequest' => $request])
        ->assertSee('Entitas Aktif untuk Review · NPWP 12345678901')
        ->assertDontSee('Entitas Nonaktif untuk Review');
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
    ProcurementRequestItem::factory()->create(['procurement_request_id' => $request->id]);
    $subsidiary = Subsidiary::factory()->create();
    $request->assignSubsidiary($subsidiary, $admin);

    Livewire::test(ProcurementReviewPanel::class, ['procurementRequest' => $request])
        ->assertSee('Teruskan ke Negosiasi')
        ->call('confirmStartNegotiation')
        ->assertSet('negotiationModal', true)
        ->assertSee('Mulai tahap negosiasi harga?')
        ->call('startNegotiation')
        ->assertSet('negotiationModal', false)
        ->assertHasNoErrors()
        ->assertSee('Negosiasi Harga');

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_NEGOTIATION);
    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $request->id,
        'user_id' => $admin->id,
        'status' => ProcurementRequest::STATUS_NEGOTIATION,
    ]);
});

test('negotiation panel is shown and subsidiary selection is hidden after negotiation starts', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $request = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    ProcurementRequestItem::factory()->create(['procurement_request_id' => $request->id]);
    $subsidiary = Subsidiary::factory()->create();
    $request->assignSubsidiary($subsidiary, $admin);
    $request->startNegotiation($admin);

    Livewire::test(ProcurementReviewPanel::class, ['procurementRequest' => $request])
        ->assertSee($subsidiary->name)
        ->assertDontSee('Anak Perusahaan aktif')
        ->assertDontSee('Simpan Penetapan');

    Livewire::test(ProcurementRequestDetail::class, ['procurementRequest' => $request])
        ->assertSee('Negosiasi Harga Per Item')
        ->assertSet('activeTab', 'negosiasi')
        ->assertSee('Kirim Penawaran');

    $school = School::factory()->create();
    $schoolUser = User::factory()->school()->create(['school_id' => $school->id]);
    $schoolRequest = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    $schoolItem = ProcurementRequestItem::factory()->create(['procurement_request_id' => $schoolRequest->id]);
    $schoolRequest->assignSubsidiary(Subsidiary::factory()->create(), $admin);
    $schoolRequest->startNegotiation($admin);

    Livewire::actingAs($schoolUser)
        ->test(ProcurementRequestDetail::class, ['procurementRequest' => $schoolRequest])
        ->assertSee('Negosiasi Harga Per Item')
        ->assertSee('Menunggu Admin/Superadmin mengajukan penawaran')
        ->assertDontSee('Ajukan Penawaran Balik')
        ->assertDontSee('Masukkan harga');

    ProcurementNegotiation::offer(
        $schoolItem,
        $admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        95000
    );

    Livewire::actingAs($schoolUser)
        ->test(ProcurementRequestDetail::class, ['procurementRequest' => $schoolRequest])
        ->assertSee('Ajukan Penawaran Balik')
        ->assertSee('Masukkan harga');

    ProcurementNegotiation::offer(
        $schoolItem,
        $schoolUser,
        ProcurementNegotiation::OFFERED_BY_SCHOOL,
        90000
    );

    Livewire::actingAs($admin)
        ->test(ProcurementRequestDetail::class, ['procurementRequest' => $schoolRequest])
        ->assertSee('Ajukan Penawaran')
        ->assertSee('Masukkan harga');

    Livewire::actingAs($schoolUser)
        ->test(ProcurementRequestDetail::class, ['procurementRequest' => $schoolRequest])
        ->assertSee('Menunggu Admin/Superadmin mengajukan penawaran')
        ->assertDontSee('Masukkan harga');

    Livewire::actingAs($admin)
        ->test(ProcurementReviewPanel::class, ['procurementRequest' => $request])
        ->set('subsidiaryId', Subsidiary::factory()->create()->id)
        ->call('assignSubsidiary')
        ->assertStatus(403);
});

test('negotiation model enforces admin first and alternates offer turns', function () {
    $school = School::factory()->create();
    $schoolUser = User::factory()->school()->create(['school_id' => $school->id]);
    $admin = User::factory()->admin()->create();
    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
    ]);
    $request->assignSubsidiary(Subsidiary::factory()->create(), $admin);
    $request->startNegotiation($admin);

    expect(fn () => ProcurementNegotiation::offer(
        $item,
        $schoolUser,
        ProcurementNegotiation::OFFERED_BY_SCHOOL,
        90000
    ))->toThrow(\LogicException::class, 'Giliran penawaran saat ini adalah Admin Pusat.');

    $adminOffer = ProcurementNegotiation::offer(
        $item,
        $admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        95000
    );
    expect($item->fresh()->nextOfferBy())->toBe(ProcurementNegotiation::OFFERED_BY_SCHOOL);

    ProcurementNegotiation::offer(
        $item,
        $schoolUser,
        ProcurementNegotiation::OFFERED_BY_SCHOOL,
        90000
    );
    expect($item->fresh()->nextOfferBy())->toBe(ProcurementNegotiation::OFFERED_BY_ADMIN);

    expect($adminOffer->refresh()->status)->toBe(ProcurementNegotiation::STATUS_COUNTERED);
});

test('admin can accept school offer without rejection controls', function () {
    $school = School::factory()->create();
    $schoolUser = User::factory()->school()->create(['school_id' => $school->id]);
    $admin = User::factory()->admin()->create();
    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
    ]);
    $request->assignSubsidiary(Subsidiary::factory()->create(), $admin);
    $request->startNegotiation($admin);

    $schoolOffer = ProcurementNegotiation::offer(
        $item,
        $admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        95000
    );
    ProcurementNegotiation::offer(
        $item,
        $schoolUser,
        ProcurementNegotiation::OFFERED_BY_SCHOOL,
        90000
    );
    $schoolOffer->refresh();
    $latestSchoolOffer = ProcurementNegotiation::query()
        ->where('procurement_request_item_id', $item->id)
        ->latest('round_number')
        ->firstOrFail();

    Livewire::actingAs($admin)
        ->test(\App\Livewire\Procurement\NegotiationPanel::class, ['procurementRequest' => $request])
        ->assertSee('Terima Penawaran Sekolah')
        ->assertDontSee('Tolak Tawaran Harga')
        ->assertDontSee('Tolak Tawaran')
        ->call('accept', $latestSchoolOffer->id)
        ->assertHasNoErrors();

    expect($latestSchoolOffer->fresh()->status)->toBe(ProcurementNegotiation::STATUS_ACCEPTED)
        ->and($latestSchoolOffer->fresh()->provider_approved_at)->not->toBeNull()
        ->and($item->fresh()->official_price)->toEqual(90000)
        ->and($request->fresh()->status)->toBe(ProcurementRequest::STATUS_PRICE_AGREED);
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

test('review panel only exposes rule-based tax evaluation', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $request = ProcurementRequest::factory()->create();

    $component = Livewire::test(ProcurementReviewPanel::class, ['procurementRequest' => $request])
        ->assertSee('Evaluasi Aturan Pajak')
        ->assertDontSee('Tarif PPN')
        ->assertDontSee('saveTaxConfiguration');

    expect(property_exists($component->instance(), 'ppnRate'))->toBeFalse()
        ->and(method_exists($component->instance(), 'saveTaxConfiguration'))->toBeFalse();
});
