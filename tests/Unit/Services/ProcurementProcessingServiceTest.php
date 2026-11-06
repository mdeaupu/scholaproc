<?php

use App\Livewire\Procurement\ProcurementProcessingPanel;
use App\Models\ProcurementDocument;
use App\Models\ProcurementNegotiation;
use App\Models\ProcurementReceiptItem;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Models\Subsidiary;
use App\Models\User;
use App\Services\DocumentGenerationService;
use App\Services\ProcurementProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeProcessingRequest(array $overrides = []): array
{
    Storage::fake('public');
    $admin = User::factory()->admin()->create();
    $letterheadPath = UploadedFile::fake()->image('kop.png')->store('subsidiary-letterheads', 'public');
    $subsidiary = Subsidiary::factory()->create(['letterhead_path' => $letterheadPath]);

    $request = ProcurementRequest::factory()->create(array_merge([
        'status' => ProcurementRequest::STATUS_VERIFIED,
        'subsidiary_id' => null,
        'tax_configuration_validated' => true,
        'tax_configuration_basis' => 'validasi test',
        'tax_configuration_validated_by' => $admin->id,
        'tax_configuration_validated_at' => now(),
        'is_taxable' => true,
        'ppn_rate' => 11,
        'pph_22_rate' => 1.5,
        'pph_23_rate' => 2,
        'subtotal' => 100000,
        'tax_amount' => 11000,
        'grand_total' => 111000,
        'price_snapshot' => ['items' => []],
        'tax_snapshot' => [],
    ], $overrides));

    $request->assignSubsidiary($subsidiary, $admin);
    $request->update(['status' => ProcurementRequest::STATUS_PROCESSING]);

    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'quantity' => 10,
        'official_price' => 10000,
        'is_pph' => false,
        'negotiation_status' => 'accepted',
    ]);

    $request->lockTotals();
    ProcurementDocument::create([
        'procurement_request_id' => $request->id,
        'document_type' => 'purchase_order',
        'document_number' => "SP/{$request->id}/".now()->format('Y'),
        'document_date' => now()->toDateString(),
        'issued_by' => $admin->id,
    ])->issue();

    $request->signatories()->create(['name' => 'Kepsek', 'role' => 'headmaster']);
    $request->signatories()->create(['name' => 'Pemeriksa', 'role' => 'inspector']);
    $request->signatories()->create(['name' => 'Bendahara', 'role' => 'treasurer']);

    return [$request, $admin, $item];
}

test('startProcessing requires price agreed totals locked and subsidiary', function () {
    $admin = User::factory()->admin()->create();
    $request = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);

    expect(fn () => app(ProcurementProcessingService::class)->startProcessing($request, $admin))
        ->toThrow(LogicException::class);
});

test('startProcessing creates processing status and purchase order', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();
    $letterheadPath = UploadedFile::fake()->image('kop.png')->store('subsidiary-letterheads', 'public');
    $subsidiary = Subsidiary::factory()->create(['letterhead_path' => $letterheadPath]);
    $request = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
        'subsidiary_id' => null,
    ]);
    $request->assignSubsidiary($subsidiary, $admin);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'quantity' => 2,
        'official_price' => 10000,
    ]);
    $request->lockTotals();
    $request->update(['status' => ProcurementRequest::STATUS_PRICE_AGREED]);
    app(DocumentGenerationService::class)->issue($request, 'purchase_order', $admin);

    app(ProcurementProcessingService::class)->startProcessing($request, $admin);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_PROCESSING)
        ->and($request->fresh()->hasIssuedDocument('purchase_order'))->toBeTrue()
        ->and($request->fresh()->hasIssuedDocument('planning'))->toBeTrue()
        ->and($request->fresh()->documents()->where('document_type', 'purchase_order')->count())->toBe(1);
});

test('price agreement issues the purchase order and processing does not issue it twice', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();
    $school = School::factory()->create();
    $schoolUser = User::factory()->school()->create(['school_id' => $school->id]);
    $letterheadPath = UploadedFile::fake()->image('kop.png')->store('subsidiary-letterheads', 'public');
    $subsidiary = Subsidiary::factory()->create(['letterhead_path' => $letterheadPath]);
    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    $request->assignSubsidiary($subsidiary, $admin);
    $request->update(['status' => ProcurementRequest::STATUS_NEGOTIATION]);
    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'negotiation_status' => 'accepted',
        'official_price' => 10000,
    ]);
    $item->negotiations()->create([
        'round_number' => 1,
        'offered_by' => ProcurementNegotiation::OFFERED_BY_ADMIN,
        'user_id' => $admin->id,
        'offered_price' => 10000,
        'status' => ProcurementNegotiation::STATUS_ACCEPTED,
        'school_approved_by' => $schoolUser->id,
        'school_approved_at' => now(),
        'provider_approved_by' => $admin->id,
        'provider_approved_at' => now(),
    ]);

    $request->finalizePriceAgreement($admin);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_PRICE_AGREED)
        ->and($request->fresh()->hasIssuedDocument('negotiation'))->toBeTrue()
        ->and($request->fresh()->hasIssuedDocument('purchase_order'))->toBeTrue();

    app(ProcurementProcessingService::class)->startProcessing($request, $admin);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_PROCESSING)
        ->and($request->fresh()->documents()->where('document_type', 'purchase_order')->count())->toBe(1);
});

test('delivery partial succeeds and over shipment fails', function () {
    [$request, $admin, $item] = makeProcessingRequest();

    $delivery = app(ProcurementProcessingService::class)->recordDelivery(
        $request,
        $admin,
        now()->toDateString(),
        [$item->id => 4],
        'partial'
    );

    expect($delivery->items->sum('quantity'))->toBe(4);

    expect(fn () => app(ProcurementProcessingService::class)->recordDelivery(
        $request,
        $admin,
        now()->toDateString(),
        [$item->id => 8]
    ))->toThrow(ValidationException::class);
});

test('receipt draft does not change status and verified receipt issues bast', function () {
    [$request, $admin, $item] = makeProcessingRequest();

    app(ProcurementProcessingService::class)->recordDelivery(
        $request,
        $admin,
        now()->toDateString(),
        [$item->id => 4]
    );

    $receipt = app(ProcurementProcessingService::class)->recordReceipt(
        $request,
        $admin,
        now()->toDateString(),
        [$item->id => ['quantity' => 4, 'condition' => 'good']],
        'draft'
    );

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_PROCESSING)
        ->and($receipt->verified_at)->toBeNull();

    app(ProcurementProcessingService::class)->verifyReceipt($receipt, $admin);

    $inspection = $request->fresh()
        ->documents()
        ->where('document_type', 'inspection')
        ->latest('version')
        ->firstOrFail();

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_PARTIALLY_RECEIVED)
        ->and($request->fresh()->hasIssuedDocument('bast'))->toBeTrue()
        ->and($request->fresh()->hasIssuedDocument('inspection'))->toBeTrue()
        ->and(data_get($inspection->document_snapshot, 'stage.inspection_date_numeric'))
        ->toBe($receipt->fresh()->verified_at->format('d-m-Y'))
        ->and(data_get($inspection->document_snapshot, 'stage.inspection_date_words'))
        ->not->toBe('-')
        ->and(data_get($inspection->document_snapshot, 'stage.purchase_order.number'))
        ->not->toBeEmpty();

    app(DocumentGenerationService::class)->issue($request, 'inspection', $admin, [
        'receipt_id' => $receipt->id,
    ]);

    expect($request->fresh()->documents()->where('document_type', 'inspection')->count())->toBe(1);
});

test('receipt quantity cap fails', function () {
    [$request, $admin, $item] = makeProcessingRequest();

    app(ProcurementProcessingService::class)->recordDelivery(
        $request,
        $admin,
        now()->toDateString(),
        [$item->id => 4]
    );

    $receipt = app(ProcurementProcessingService::class)->recordReceipt(
        $request,
        $admin,
        now()->toDateString(),
        [$item->id => ['quantity' => 4, 'condition' => 'good']]
    );

    app(ProcurementProcessingService::class)->verifyReceipt($receipt, $admin);

    expect(fn () => app(ProcurementProcessingService::class)->recordReceipt(
        $request,
        $admin,
        now()->toDateString(),
        [$item->id => ['quantity' => 1, 'condition' => 'good']]
    ))->toThrow(ValidationException::class);
});

test('damaged receipt creates disputed status', function () {
    [$request, $admin, $item] = makeProcessingRequest();

    app(ProcurementProcessingService::class)->recordDelivery(
        $request,
        $admin,
        now()->toDateString(),
        [$item->id => 10]
    );

    $receipt = app(ProcurementProcessingService::class)->recordReceipt(
        $request,
        $admin,
        now()->toDateString(),
        [$item->id => ['quantity' => 10, 'condition' => 'damaged', 'issue_notes' => 'Rusak']]
    );

    app(ProcurementProcessingService::class)->verifyReceipt($receipt, $admin);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_DISPUTED)
        ->and(ProcurementReceiptItem::query()->where('follow_up_status', 'open')->exists())->toBeTrue();
});

test('invoice gating requires full receipt bast tax and totals', function () {
    [$request, $admin, $item] = makeProcessingRequest();

    expect(fn () => app(ProcurementProcessingService::class)->processInvoice($request, $admin))
        ->toThrow(LogicException::class);
});

test('invoice and payment flow succeeds', function () {
    [$request, $admin, $item] = makeProcessingRequest();

    app(ProcurementProcessingService::class)->recordDelivery(
        $request,
        $admin,
        now()->toDateString(),
        [$item->id => 10]
    );

    $receipt = app(ProcurementProcessingService::class)->recordReceipt(
        $request,
        $admin,
        now()->toDateString(),
        [$item->id => ['quantity' => 10, 'condition' => 'good']]
    );
    app(ProcurementProcessingService::class)->verifyReceipt($receipt, $admin);

    $invoice = app(ProcurementProcessingService::class)->processInvoice($request, $admin);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_INVOICED)
        ->and($invoice->total_amount)->toBe('111000.00')
        ->and($request->fresh()->hasIssuedDocument('invoice'))->toBeTrue();

    $payment = app(ProcurementProcessingService::class)->recordPayment(
        $invoice,
        $admin,
        111000,
        'transfer',
        'REF1',
        now()->toDateString()
    );

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_PAYMENT_PENDING);

    app(ProcurementProcessingService::class)->confirmPayment($payment, $admin);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_PAYMENT_PENDING)
        ->and($request->fresh()->payment_confirmed_at)->not->toBeNull()
        ->and($request->fresh()->hasIssuedDocument('receipt'))->toBeFalse();

    $request->fresh()->complete($admin);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_COMPLETED)
        ->and($request->fresh()->hasIssuedDocument('receipt'))->toBeTrue()
        ->and($request->fresh()->hasIssuedDocument('cover'))->toBeTrue()
        ->and(data_get(
            $request->fresh()->documents()->where('document_type', 'receipt')->first()->document_snapshot,
            'transaction.payment.method'
        ))->toBe('transfer')
        ->and(data_get(
            $request->fresh()->documents()->where('document_type', 'receipt')->first()->document_snapshot,
            'transaction.payment.reference'
        ))->toBe('REF1')
        ->and(data_get(
            $request->fresh()->documents()->where('document_type', 'receipt')->first()->document_snapshot,
            'transaction.receipt_signatories.treasurer'
        ))->toBe('Bendahara');
});

test('payment mismatch and duplicate fail', function () {
    [$request, $admin, $item] = makeProcessingRequest();

    app(ProcurementProcessingService::class)->recordDelivery($request, $admin, now()->toDateString(), [$item->id => 10]);
    $receipt = app(ProcurementProcessingService::class)->recordReceipt($request, $admin, now()->toDateString(), [$item->id => ['quantity' => 10, 'condition' => 'good']]);
    app(ProcurementProcessingService::class)->verifyReceipt($receipt, $admin);
    $invoice = app(ProcurementProcessingService::class)->processInvoice($request, $admin);

    expect(fn () => app(ProcurementProcessingService::class)->recordPayment($invoice, $admin, 1000, 'transfer', null, now()->toDateString()))
        ->toThrow(LogicException::class);

    app(ProcurementProcessingService::class)->recordPayment($invoice, $admin, 111000, 'transfer', null, now()->toDateString());

    expect(fn () => app(ProcurementProcessingService::class)->recordPayment($invoice, $admin, 111000, 'transfer', null, now()->toDateString()))
        ->toThrow(LogicException::class);
});

test('completion gating requires all conditions', function () {
    [$request, $admin, $item] = makeProcessingRequest();

    expect(fn () => $request->complete($admin))->toThrow(Exception::class);
});

test('completion succeeds after full flow', function () {
    [$request, $admin, $item] = makeProcessingRequest();

    app(ProcurementProcessingService::class)->recordDelivery($request, $admin, now()->toDateString(), [$item->id => 10]);
    $receipt = app(ProcurementProcessingService::class)->recordReceipt($request, $admin, now()->toDateString(), [$item->id => ['quantity' => 10, 'condition' => 'good']]);
    app(ProcurementProcessingService::class)->verifyReceipt($receipt, $admin);
    $invoice = app(ProcurementProcessingService::class)->processInvoice($request, $admin);
    $payment = app(ProcurementProcessingService::class)->recordPayment($invoice, $admin, 111000, 'transfer', null, now()->toDateString());
    app(ProcurementProcessingService::class)->confirmPayment($payment, $admin);

    $request->refresh();
    $request->complete($admin);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_COMPLETED)
        ->and($request->fresh()->hasIssuedDocument('receipt'))->toBeTrue()
        ->and($request->fresh()->hasIssuedDocument('cover'))->toBeTrue();
});

test('school cannot use processing panel', function () {
    [$request, $admin, $item] = makeProcessingRequest();
    $schoolUser = User::factory()->school()->create();

    Livewire::actingAs($schoolUser)
        ->test(ProcurementProcessingPanel::class, ['procurementRequest' => $request])
        ->assertStatus(403);
});
