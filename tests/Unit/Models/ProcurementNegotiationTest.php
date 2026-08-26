<?php

use App\Models\ProcurementNegotiation;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->schoolModel = School::factory()->create();

    $this->school = User::factory()->create([
        'role' => 'school',
        'school_id' => $this->schoolModel->id,
    ]);

    $this->admin = User::factory()->create(['role' => 'admin']);

    $this->supplier = Supplier::factory()->create();

    $this->procurement = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_SUPPLIER_ASSIGNED,
        'school_id' => $this->schoolModel->id,
        'supplier_id' => $this->supplier->id,
    ]);

    $this->item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $this->procurement->id,
        'estimated_price' => 100000,
        'official_price' => null,
        'negotiation_status' => 'not_started',
    ]);
});

// ─── offer() Tests ─────────────────────────────────────────────

test('admin can create first round offer', function () {
    $nego = ProcurementNegotiation::offer(
        $this->item,
        $this->admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        95000,
        'Harga penawaran supplier'
    );

    expect($nego)->toBeInstanceOf(ProcurementNegotiation::class)
        ->and($nego->round_number)->toBe(1)
        ->and($nego->offered_by)->toBe(ProcurementNegotiation::OFFERED_BY_ADMIN)
        ->and($nego->offered_price)->toEqual(95000)
        ->and($nego->status)->toBe(ProcurementNegotiation::STATUS_PENDING)
        ->and($nego->notes)->toBe('Harga penawaran supplier');

    expect($this->item->refresh()->negotiation_status)->toBe('negotiating');
});

test('school can counter-offer after admin offer', function () {
    ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 95000);

    $counter = ProcurementNegotiation::offer(
        $this->item,
        $this->school,
        ProcurementNegotiation::OFFERED_BY_SCHOOL,
        85000,
        'Tawaran balik sekolah'
    );

    expect($counter->round_number)->toBe(2)
        ->and($counter->offered_by)->toBe(ProcurementNegotiation::OFFERED_BY_SCHOOL)
        ->and($counter->offered_price)->toEqual(85000);

    $round1 = ProcurementNegotiation::where('round_number', 1)->first();
    expect($round1->status)->toBe(ProcurementNegotiation::STATUS_COUNTERED);
});

test('admin can counter after school counter', function () {
    ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 95000);
    ProcurementNegotiation::offer($this->item, $this->school, ProcurementNegotiation::OFFERED_BY_SCHOOL, 85000);

    $counter2 = ProcurementNegotiation::offer(
        $this->item,
        $this->admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        90000,
        'Harga mufakat'
    );

    expect($counter2->round_number)->toBe(3);

    $round2 = ProcurementNegotiation::where('round_number', 2)->first();
    expect($round2->status)->toBe(ProcurementNegotiation::STATUS_COUNTERED);
});

test('throws exception when offering on settled item', function () {
    $this->item->update(['negotiation_status' => 'accepted']);

    expect(fn () => ProcurementNegotiation::offer(
        $this->item,
        $this->admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        80000
    ))->toThrow(\Exception::class, 'Negosiasi untuk item ini sudah selesai');
});

test('throws exception when offering without supplier', function () {
    $this->procurement->update(['supplier_id' => null]);

    expect(fn () => ProcurementNegotiation::offer(
        $this->item,
        $this->admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        95000
    ))->toThrow(\Exception::class, 'Supplier belum ditentukan');
});

test('throws exception with invalid offered_by value', function () {
    expect(fn () => ProcurementNegotiation::offer(
        $this->item,
        $this->admin,
        'invalid_role',
        95000
    ))->toThrow(\Exception::class, 'Pihak penawar tidak valid');
});

test('offer creates record in database', function () {
    ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 95000);

    $this->assertDatabaseHas('procurement_negotiations', [
        'procurement_request_item_id' => $this->item->id,
        'round_number' => 1,
        'offered_by' => ProcurementNegotiation::OFFERED_BY_ADMIN,
        'offered_price' => 95000,
        'status' => ProcurementNegotiation::STATUS_PENDING,
    ]);
});

// ─── accept() Tests ────────────────────────────────────────────

test('school can accept admin offer and sets official price', function () {
    $nego = ProcurementNegotiation::offer(
        $this->item,
        $this->admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        90000
    );

    $nego->accept($this->school);

    expect($nego->refresh()->status)->toBe(ProcurementNegotiation::STATUS_ACCEPTED);
    expect($this->item->refresh()->official_price)->toEqual(90000);
    expect($this->item->negotiation_status)->toBe('accepted');
});

test('accept records history on procurement request', function () {
    $nego = ProcurementNegotiation::offer(
        $this->item,
        $this->admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        90000
    );

    $nego->accept($this->school);

    $this->assertDatabaseHas('procurement_request_histories', [
        'procurement_request_id' => $this->procurement->id,
        'user_id' => $this->school->id,
    ]);
});

test('admin cannot accept offer (only school)', function () {
    $nego = ProcurementNegotiation::offer(
        $this->item,
        $this->admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        90000
    );

    expect(fn () => $nego->accept($this->admin))
        ->toThrow(\Exception::class, 'Hanya pihak sekolah yang dapat menerima/menolak tawaran harga.');
});

test('throws exception when accepting non-pending offer', function () {
    $nego = ProcurementNegotiation::offer(
        $this->item,
        $this->admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        90000
    );

    $nego->update(['status' => ProcurementNegotiation::STATUS_COUNTERED]);

    expect(fn () => $nego->accept($this->school))
        ->toThrow(\Exception::class, 'Hanya tawaran berstatus pending yang dapat diterima.');
});

// ─── reject() Tests ────────────────────────────────────────────

test('school can reject admin offer', function () {
    $nego = ProcurementNegotiation::offer(
        $this->item,
        $this->admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        90000
    );

    $nego->reject($this->school, 'Harga terlalu tinggi');

    expect($nego->refresh()->status)->toBe(ProcurementNegotiation::STATUS_REJECTED);
    expect($this->item->refresh()->negotiation_status)->toBe('rejected');
});

test('reject without reason is allowed', function () {
    $nego = ProcurementNegotiation::offer(
        $this->item,
        $this->admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        90000
    );

    $nego->reject($this->school);

    expect($nego->refresh()->status)->toBe(ProcurementNegotiation::STATUS_REJECTED);
});

test('admin cannot reject (only school)', function () {
    $nego = ProcurementNegotiation::offer(
        $this->item,
        $this->admin,
        ProcurementNegotiation::OFFERED_BY_ADMIN,
        90000
    );

    expect(fn () => $nego->reject($this->admin, 'Tolak'))
        ->toThrow(\Exception::class, 'Hanya pihak sekolah yang dapat menerima/menolak tawaran harga.');
});

// ─── isLatestRound() Tests ────────────────────────────────────

test('isLatestRound returns true for most recent round', function () {
    $round1 = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 95000);
    $round2 = ProcurementNegotiation::offer($this->item, $this->school, ProcurementNegotiation::OFFERED_BY_SCHOOL, 85000);

    expect($round1->isLatestRound())->toBeFalse();
    expect($round2->isLatestRound())->toBeTrue();
});

// ─── isFromSchool() / isFromSupplierRepresentative() Tests ────

test('isFromSchool and isFromSupplierRepresentative return correct values', function () {
    $adminNego = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 95000);
    $schoolNego = ProcurementNegotiation::offer($this->item, $this->school, ProcurementNegotiation::OFFERED_BY_SCHOOL, 85000);

    expect($adminNego->isFromSchool())->toBeFalse();
    expect($adminNego->isFromSupplierRepresentative())->toBeTrue();
    expect($schoolNego->isFromSchool())->toBeTrue();
    expect($schoolNego->isFromSupplierRepresentative())->toBeFalse();
});

// ─── isSettled() Tests ────────────────────────────────────────

test('isSettled returns true for accepted or rejected', function () {
    $pending = ProcurementNegotiation::factory()->pending()->create([
        'procurement_request_item_id' => $this->item->id,
        'round_number' => 1,
    ]);
    $accepted = ProcurementNegotiation::factory()->accepted()->create([
        'procurement_request_item_id' => $this->item->id,
        'round_number' => 2,
    ]);
    $rejected = ProcurementNegotiation::factory()->rejected()->create([
        'procurement_request_item_id' => $this->item->id,
        'round_number' => 3,
    ]);

    expect($pending->isSettled())->toBeFalse();
    expect($accepted->isSettled())->toBeTrue();
    expect($rejected->isSettled())->toBeTrue();
});

// ─── Scopes Tests ─────────────────────────────────────────────

test('scopeForItem filters by item', function () {
    $item2 = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $this->procurement->id,
    ]);

    ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 95000);
    ProcurementNegotiation::offer($item2, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 80000);

    $results = ProcurementNegotiation::forItem($this->item->id)->get();
    expect($results->count())->toBe(1);
    expect($results->first()->procurement_request_item_id)->toBe($this->item->id);
});

test('scopePending filters by pending status', function () {
    ProcurementNegotiation::factory()->pending()->create([
        'procurement_request_item_id' => $this->item->id,
        'round_number' => 1,
    ]);
    ProcurementNegotiation::factory()->accepted()->create([
        'procurement_request_item_id' => $this->item->id,
        'round_number' => 2,
    ]);

    $pending = ProcurementNegotiation::pending()->get();
    expect($pending->count())->toBe(1);
    expect($pending->first()->status)->toBe(ProcurementNegotiation::STATUS_PENDING);
});

// ─── ProcurementRequest Negotiation Integration Tests ──────────

test('allItemsNegotiationSettled returns false when items not settled', function () {
    expect($this->procurement->allItemsNegotiationSettled())->toBeFalse();
});

test('allItemsNegotiationSettled returns true when all items accepted', function () {
    $nego = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 90000);
    $nego->accept($this->school);

    expect($this->procurement->refresh()->allItemsNegotiationSettled())->toBeTrue();
});

test('allItemsNegotiationSettled returns true when all items rejected', function () {
    $nego = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 90000);
    $nego->reject($this->school, 'Too expensive');

    expect($this->procurement->refresh()->allItemsNegotiationSettled())->toBeTrue();
});

test('allItemsNegotiationSettled returns true with mixed accepted and rejected', function () {
    $item2 = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $this->procurement->id,
        'negotiation_status' => 'not_started',
    ]);

    $nego1 = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 90000);
    $nego1->accept($this->school);

    $nego2 = ProcurementNegotiation::offer($item2, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 80000);
    $nego2->reject($this->school, 'Too cheap');

    expect($this->procurement->refresh()->allItemsNegotiationSettled())->toBeTrue();
});

test('negotiationProgress returns correct counts', function () {
    $item2 = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $this->procurement->id,
        'negotiation_status' => 'not_started',
    ]);

    $nego1 = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 90000);
    $nego1->accept($this->school);

    $progress = $this->procurement->refresh()->negotiationProgress();

    expect($progress['total'])->toBe(2)
        ->and($progress['accepted'])->toBe(1)
        ->and($progress['rejected'])->toBe(0)
        ->and($progress['pending'])->toBe(1)
        ->and($progress['settled'])->toBe(1)
        ->and($progress['percentage'])->toEqual(50);
});

test('hasOfficialPrices returns true when item has official price from negotiation', function () {
    expect($this->procurement->hasOfficialPrices())->toBeFalse();

    $nego = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 90000);
    $nego->accept($this->school);

    expect($this->procurement->refresh()->hasOfficialPrices())->toBeTrue();
});

test('hasOfficialPrices returns true for rejected items without official price', function () {
    $nego = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 90000);
    $nego->reject($this->school, 'Too expensive');

    expect($this->procurement->refresh()->hasOfficialPrices())->toBeTrue();
});

test('canStartNegotiation returns true when supplier assigned', function () {
    expect($this->procurement->canStartNegotiation())->toBeTrue();
});

test('canStartNegotiation returns false when no supplier', function () {
    $this->procurement->update(['supplier_id' => null]);
    expect($this->procurement->canStartNegotiation())->toBeFalse();
});

test('canComplete requires all items settled', function () {
    $this->procurement->update(['status' => ProcurementRequest::STATUS_ITEMS_PREPARED]);
    $this->procurement->signatories()->create(['name' => 'Kepsek', 'role' => 'headmaster']);
    $this->procurement->signatories()->create(['name' => 'Pemeriksa', 'role' => 'inspector']);
    $this->procurement->signatories()->create(['name' => 'Bendahara', 'role' => 'treasurer']);

    expect($this->procurement->canComplete())->toBeFalse();

    $nego = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 90000);
    $nego->accept($this->school);

    expect($this->procurement->refresh()->canComplete())->toBeTrue();
});
