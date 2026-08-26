<?php

use App\Livewire\Procurement\NegotiationPanel;
use App\Models\ProcurementNegotiation;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

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

// ─── Mount & Rendering Tests ──────────────────────────────────

test('renders negotiation panel successfully', function () {
    actingAs($this->admin);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->assertStatus(200);
});

test('selects first item by default on mount', function () {
    actingAs($this->admin);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->assertSet('selectedItemId', $this->item->id);
});

// ─── offer() Tests ────────────────────────────────────────────

test('admin can submit offer via livewire', function () {
    actingAs($this->admin);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('selectedItemId', $this->item->id)
        ->set('offerPrice', 95000)
        ->set('offerNotes', 'Harga penawaran supplier')
        ->call('offer')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('procurement_negotiations', [
        'procurement_request_item_id' => $this->item->id,
        'offered_by' => ProcurementNegotiation::OFFERED_BY_ADMIN,
        'offered_price' => 95000,
        'status' => ProcurementNegotiation::STATUS_PENDING,
    ]);

    expect($this->item->refresh()->negotiation_status)->toBe('negotiating');
});

test('school can submit counter-offer via livewire', function () {
    ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 95000);

    actingAs($this->school);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('selectedItemId', $this->item->id)
        ->set('offerPrice', 85000)
        ->set('offerNotes', 'Tawaran balik sekolah')
        ->call('offer')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('procurement_negotiations', [
        'procurement_request_item_id' => $this->item->id,
        'offered_by' => ProcurementNegotiation::OFFERED_BY_SCHOOL,
        'offered_price' => 85000,
        'round_number' => 2,
    ]);

    $round1 = ProcurementNegotiation::where('round_number', 1)->first();
    expect($round1->status)->toBe(ProcurementNegotiation::STATUS_COUNTERED);
});

test('offer fails validation when price is zero', function () {
    actingAs($this->admin);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('selectedItemId', $this->item->id)
        ->set('offerPrice', 0)
        ->call('offer')
        ->assertHasErrors(['offerPrice']);
});

test('offer fails validation when price is negative', function () {
    actingAs($this->admin);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('selectedItemId', $this->item->id)
        ->set('offerPrice', -1000)
        ->call('offer')
        ->assertHasErrors(['offerPrice']);
});

test('offer resets fields after successful submission', function () {
    actingAs($this->admin);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('selectedItemId', $this->item->id)
        ->set('offerPrice', 95000)
        ->set('offerNotes', 'Some notes')
        ->call('offer')
        ->assertSet('offerPrice', 0)
        ->assertSet('offerNotes', '');
});

test('offer on settled item does not create new negotiation', function () {
    $nego = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 90000);
    $nego->accept($this->school);

    actingAs($this->admin);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('selectedItemId', $this->item->id)
        ->set('offerPrice', 80000)
        ->call('offer');

    // Still only 1 negotiation record
    expect(ProcurementNegotiation::where('procurement_request_item_id', $this->item->id)->count())->toBe(1);
});

// ─── accept() Tests ────────────────────────────────────────────

test('school can accept offer via livewire', function () {
    $nego = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 90000);

    actingAs($this->school);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('accept', $nego->id)
        ->assertHasNoErrors();

    expect($nego->refresh()->status)->toBe(ProcurementNegotiation::STATUS_ACCEPTED);
    expect($this->item->refresh()->official_price)->toEqual(90000);
    expect($this->item->negotiation_status)->toBe('accepted');
});

test('admin cannot accept offer - item status unchanged', function () {
    $nego = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 90000);

    actingAs($this->admin);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('accept', $nego->id);

    // Nego status remains pending because accept() returned early via error toast
    expect($nego->refresh()->status)->toBe(ProcurementNegotiation::STATUS_PENDING);
    expect($this->item->refresh()->official_price)->toBeNull();
});

// ─── reject() Tests ────────────────────────────────────────────

test('school can open reject modal and confirm rejection', function () {
    $nego = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 90000);

    actingAs($this->school);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('openRejectModal', $nego->id)
        ->assertSet('showRejectModal', true)
        ->assertSet('rejectingNegotiationId', $nego->id)
        ->set('rejectReason', 'Harga terlalu tinggi')
        ->call('confirmReject')
        ->assertHasNoErrors()
        ->assertSet('showRejectModal', false);

    expect($nego->refresh()->status)->toBe(ProcurementNegotiation::STATUS_REJECTED);
    expect($this->item->refresh()->negotiation_status)->toBe('rejected');
});

test('reject fails validation when reason is empty', function () {
    $nego = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 90000);

    actingAs($this->school);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('openRejectModal', $nego->id)
        ->set('rejectReason', '')
        ->call('confirmReject')
        ->assertHasErrors(['rejectReason']);
});

test('admin cannot open reject modal - nego status unchanged', function () {
    $nego = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 90000);

    actingAs($this->admin);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('openRejectModal', $nego->id);

    // Modal should not be open because error toast was called
    expect($nego->refresh()->status)->toBe(ProcurementNegotiation::STATUS_PENDING);
});

// ─── selectItem() Tests ───────────────────────────────────────

test('can switch between items', function () {
    $item2 = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $this->procurement->id,
    ]);

    actingAs($this->admin);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('selectItem', $item2->id)
        ->assertSet('selectedItemId', $item2->id);
});

// ─── showDetail() Tests ───────────────────────────────────────

test('can show negotiation detail modal', function () {
    $nego = ProcurementNegotiation::offer($this->item, $this->admin, ProcurementNegotiation::OFFERED_BY_ADMIN, 90000);

    actingAs($this->admin);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('showDetail', $nego->id)
        ->assertSet('showDetailModal', true)
        ->assertSet('detailNegotiation.id', $nego->id);
});

// ─── Multi-item Negotiation Flow ──────────────────────────────

test('can negotiate multiple items independently', function () {
    $item2 = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $this->procurement->id,
        'estimated_price' => 200000,
    ]);

    actingAs($this->admin);

    // Offer on item 1
    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('selectedItemId', $this->item->id)
        ->set('offerPrice', 90000)
        ->call('offer')
        ->assertHasNoErrors();

    // Switch to item 2 and offer
    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('selectedItemId', $item2->id)
        ->set('offerPrice', 180000)
        ->call('offer')
        ->assertHasNoErrors();

    // Accept item 1 as school
    $nego1 = ProcurementNegotiation::where('procurement_request_item_id', $this->item->id)->first();

    actingAs($this->school);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('accept', $nego1->id)
        ->assertHasNoErrors();

    // Check progress
    $progress = $this->procurement->refresh()->negotiationProgress();
    expect($progress['accepted'])->toBe(1)
        ->and($progress['pending'])->toBe(1)
        ->and($progress['percentage'])->toEqual(50);
});

test('can complete full negotiation flow - offer, counter, accept', function () {
    actingAs($this->admin);

    // Admin offers
    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('selectedItemId', $this->item->id)
        ->set('offerPrice', 95000)
        ->call('offer')
        ->assertHasNoErrors();

    // School counters
    actingAs($this->school);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('selectedItemId', $this->item->id)
        ->set('offerPrice', 85000)
        ->call('offer')
        ->assertHasNoErrors();

    // Admin counters again
    actingAs($this->admin);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->set('selectedItemId', $this->item->id)
        ->set('offerPrice', 90000)
        ->call('offer')
        ->assertHasNoErrors();

    // School accepts
    $latestNego = ProcurementNegotiation::where('procurement_request_item_id', $this->item->id)
        ->latest('round_number')
        ->first();

    actingAs($this->school);

    Livewire::test(NegotiationPanel::class, ['procurementRequest' => $this->procurement])
        ->call('accept', $latestNego->id)
        ->assertHasNoErrors();

    // Verify final state
    expect($this->item->refresh()->official_price)->toEqual(90000);
    expect($this->item->negotiation_status)->toBe('accepted');
    expect($this->procurement->refresh()->allItemsNegotiationSettled())->toBeTrue();

    // Check all rounds exist
    expect(ProcurementNegotiation::where('procurement_request_item_id', $this->item->id)->count())->toBe(3);
});
