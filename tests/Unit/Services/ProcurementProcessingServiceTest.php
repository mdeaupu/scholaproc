<?php

use App\Livewire\Procurement\ProcurementProcessingPanel;
use App\Models\ProcurementInvoice;
use App\Models\ProcurementPayment;
use App\Models\ProcurementReceipt;
use App\Models\ProcurementReceiptItem;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\Subsidiary;
use App\Models\User;
use App\Services\ProcurementProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
    ]);

    $request->lockTotals();
    \App\Models\ProcurementDocument::create([
        'procurement_request_id' => $request->id,
        'document_type' => 'purchase_order',
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
        ->toThrow(\LogicException::class);
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

    app(ProcurementProcessingService::class)->startProcessing($request, $admin);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_PROCESSING)
        ->and($request->fresh()->hasIssuedDocument('purchase_order'))->toBeTrue();
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
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
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

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_PARTIALLY_RECEIVED)
        ->and($request->fresh()->hasIssuedDocument('bast'))->toBeTrue();
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
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
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
        ->toThrow(\LogicException::class);
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
        ->and($invoice->total_amount)->toBe('111000.00');

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
        ->and($request->fresh()->hasIssuedDocument('receipt'))->toBeTrue();
});

test('payment mismatch and duplicate fail', function () {
    [$request, $admin, $item] = makeProcessingRequest();

    app(ProcurementProcessingService::class)->recordDelivery($request, $admin, now()->toDateString(), [$item->id => 10]);
    $receipt = app(ProcurementProcessingService::class)->recordReceipt($request, $admin, now()->toDateString(), [$item->id => ['quantity' => 10, 'condition' => 'good']]);
    app(ProcurementProcessingService::class)->verifyReceipt($receipt, $admin);
    $invoice = app(ProcurementProcessingService::class)->processInvoice($request, $admin);

    expect(fn () => app(ProcurementProcessingService::class)->recordPayment($invoice, $admin, 1000, 'transfer', null, now()->toDateString()))
        ->toThrow(\LogicException::class);

    app(ProcurementProcessingService::class)->recordPayment($invoice, $admin, 111000, 'transfer', null, now()->toDateString());

    expect(fn () => app(ProcurementProcessingService::class)->recordPayment($invoice, $admin, 111000, 'transfer', null, now()->toDateString()))
        ->toThrow(\LogicException::class);
});

test('completion gating requires all conditions', function () {
    [$request, $admin, $item] = makeProcessingRequest();

    expect(fn () => $request->complete($admin))->toThrow(\Exception::class);
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

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_COMPLETED);
});

test('school cannot use processing panel', function () {
    [$request, $admin, $item] = makeProcessingRequest();
    $schoolUser = User::factory()->school()->create();

    Livewire::actingAs($schoolUser)
        ->test(ProcurementProcessingPanel::class, ['procurementRequest' => $request])
        ->assertStatus(403);
});
