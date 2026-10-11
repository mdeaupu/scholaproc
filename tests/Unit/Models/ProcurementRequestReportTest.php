<?php

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @param  array<int, array{quantity: int, estimated: float, official?: float|null}>  $items
 */
function makeReportRequest(array $attributes = [], array $items = []): ProcurementRequest
{
    $request = ProcurementRequest::factory()->create(array_merge([
        'status' => ProcurementRequest::STATUS_SUBMITTED,
        'is_taxable' => false,
        'ppn_rate' => 0,
        'requested_at' => now(),
    ], $attributes));

    foreach ($items as $item) {
        ProcurementRequestItem::factory()->create([
            'procurement_request_id' => $request->id,
            'quantity' => $item['quantity'],
            'estimated_price' => $item['estimated'],
            'official_price' => $item['official'] ?? null,
        ]);
    }

    return $request->refresh();
}

test('statusLabels covers every workflow status', function () {
    $labels = ProcurementRequest::statusLabels();

    expect($labels)->toHaveKeys(ProcurementRequest::reportStatuses())
        ->and($labels)->toHaveCount(7)
        ->and(ProcurementRequest::statusLabel('supplier_assigned'))->toBe('Supplier Ditentukan')
        ->and(ProcurementRequest::statusLabel('tidak_dikenal'))->toBe('Tidak dikenal');
});

test('scopeStatus accepts a single status and an array of statuses', function () {
    $a = makeReportRequest(['status' => ProcurementRequest::STATUS_DRAFT]);
    $b = makeReportRequest(['status' => ProcurementRequest::STATUS_COMPLETED]);

    expect(ProcurementRequest::status('draft')->pluck('id')->all())->toBe([$a->id])
        ->and(ProcurementRequest::status(['draft', 'completed'])->pluck('id')->all())
        ->toEqualCanonicalizing([$a->id, $b->id]);
});

test('scopeStatus with empty values does not filter anything', function () {
    makeReportRequest();
    makeReportRequest();

    expect(ProcurementRequest::status([])->count())->toBe(2)
        ->and(ProcurementRequest::status([null, ''])->count())->toBe(2);
});

test('scopeBySchool and scopeBySupplier filter the report query', function () {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();
    $supplier = Supplier::factory()->create();

    $inA = makeReportRequest(['school_id' => $schoolA->id, 'supplier_id' => $supplier->id]);
    makeReportRequest(['school_id' => $schoolB->id]);

    expect(ProcurementRequest::bySchool($schoolA->id)->pluck('id')->all())->toBe([$inA->id])
        ->and(ProcurementRequest::bySupplier($supplier->id)->pluck('id')->all())->toBe([$inA->id]);
});

test('scopePeriod filters on requested_at inclusive', function () {
    $inside = makeReportRequest(['requested_at' => now()->startOfMonth()->addDays(5)]);
    makeReportRequest(['requested_at' => now()->subMonths(3)]);

    $result = ProcurementRequest::period(
        now()->startOfMonth()->toDateString(),
        now()->endOfMonth()->toDateString()
    )->pluck('id')->all();

    expect($result)->toBe([$inside->id]);
});

test('scopePeriod falls back to created_at when requested_at is null', function () {
    $draft = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_DRAFT,
        'requested_at' => null,
    ]);
    $draft->forceFill(['created_at' => now()->startOfMonth()->addDays(2)])->save();

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $draft->id,
        'quantity' => 1,
        'estimated_price' => 10000,
    ]);

    $result = ProcurementRequest::period(
        now()->startOfMonth()->toDateString(),
        now()->endOfMonth()->toDateString()
    )->pluck('id')->all();

    expect($result)->toBe([$draft->id]);
});

test('scopePeriod supports an open ended range', function () {
    $old = makeReportRequest(['requested_at' => now()->subYear()]);
    $recent = makeReportRequest(['requested_at' => now()]);

    expect(ProcurementRequest::period(from: now()->subDays(7)->toDateString())->pluck('id')->all())
        ->toBe([$recent->id])
        ->and(ProcurementRequest::period(to: now()->subMonths(6)->toDateString())->pluck('id')->all())
        ->toBe([$old->id]);
});

test('scopeForReport applies every filter at once', function () {
    $school = School::factory()->create();
    $supplier = Supplier::factory()->create();

    $match = makeReportRequest([
        'school_id' => $school->id,
        'supplier_id' => $supplier->id,
        'status' => ProcurementRequest::STATUS_COMPLETED,
        'requested_at' => now(),
    ]);

    makeReportRequest(['school_id' => $school->id, 'status' => ProcurementRequest::STATUS_DRAFT, 'requested_at' => now()]);
    makeReportRequest(['school_id' => $school->id, 'supplier_id' => $supplier->id, 'status' => ProcurementRequest::STATUS_COMPLETED, 'requested_at' => now()->subYear()]);

    $result = ProcurementRequest::forReport([
        'date_from' => now()->startOfYear()->toDateString(),
        'date_to' => now()->endOfYear()->toDateString(),
        'school_id' => $school->id,
        'supplier_id' => $supplier->id,
        'status' => [ProcurementRequest::STATUS_COMPLETED],
    ])->pluck('id')->all();

    expect($result)->toBe([$match->id]);
});

test('scopeWithReportTotals exposes per request aggregates', function () {
    $request = makeReportRequest(
        ['is_taxable' => true, 'ppn_rate' => 10],
        [
            ['quantity' => 2, 'estimated' => 100000, 'official' => 90000],
            ['quantity' => 1, 'estimated' => 50000, 'official' => 40000],
        ]
    );

    $row = ProcurementRequest::withReportTotals()->findOrFail($request->id);

    expect((float) $row->report_estimated_subtotal)->toEqual(250000.0)
        ->and((float) $row->report_base_subtotal)->toEqual(220000.0)
        ->and((float) $row->report_effective_total)->toEqual(242000.0)
        ->and((int) $row->report_total_quantity)->toBe(3)
        ->and((int) $row->report_total_item_types)->toBe(2);
});

test('report_effective_total uses the locked grand_total snapshot when available', function () {
    $request = makeReportRequest(
        ['is_taxable' => true, 'ppn_rate' => 10],
        [['quantity' => 1, 'estimated' => 100000, 'official' => 90000]]
    );

    $request->forceFill([
        'subtotal' => 90000,
        'tax_amount' => 9000,
        'grand_total' => 99000,
        'totals_locked_at' => now(),
    ])->save();

    $row = ProcurementRequest::withReportTotals()->findOrFail($request->id);

    expect((float) $row->report_effective_total)->toEqual(99000.0);
});

test('report_effective_total falls back to estimated price when no official price exists', function () {
    $request = makeReportRequest(
        ['is_taxable' => true, 'ppn_rate' => 10],
        [['quantity' => 1, 'estimated' => 200000, 'official' => null]]
    );

    $row = ProcurementRequest::withReportTotals()->findOrFail($request->id);

    expect((float) $row->report_effective_total)->toEqual(220000.0);
});

test('report aggregates are zero for a request without items', function () {
    $request = makeReportRequest();

    $row = ProcurementRequest::withReportTotals()->findOrFail($request->id);

    expect((float) $row->report_estimated_subtotal)->toEqual(0.0)
        ->and((float) $row->report_effective_total)->toEqual(0.0)
        ->and((int) $row->report_total_quantity)->toBe(0);
});

test('reportSummary aggregates totals, counts and averages', function () {
    $completed = makeReportRequest(
        ['status' => ProcurementRequest::STATUS_COMPLETED],
        [['quantity' => 2, 'estimated' => 100000, 'official' => 100000]]
    );
    $completed->forceFill(['grand_total' => 100000, 'totals_locked_at' => now()])->save();

    makeReportRequest(
        ['status' => ProcurementRequest::STATUS_REJECTED],
        [['quantity' => 1, 'estimated' => 50000, 'official' => 50000]]
    );

    $summary = ProcurementRequest::reportSummary(ProcurementRequest::query());

    expect($summary['total_transactions'])->toBe(2)
        ->and($summary['total_quantity'])->toBe(3)
        ->and($summary['total_item_types'])->toBe(2)
        ->and($summary['total_estimated'])->toEqual(250000.0)
        ->and($summary['total_official'])->toEqual(150000.0)
        ->and($summary['average_value'])->toEqual(75000.0)
        ->and($summary['saving_amount'])->toEqual(100000.0)
        ->and($summary['completed_count'])->toBe(1)
        ->and($summary['rejected_count'])->toBe(1)
        ->and($summary['locked_count'])->toBe(1)
        ->and($summary['unsuppliered_count'])->toBe(2);
});

test('reportSummary returns zeroed values when nothing matches the filter', function () {
    makeReportRequest();

    $summary = ProcurementRequest::reportSummary(ProcurementRequest::status('completed'));

    expect($summary['total_transactions'])->toBe(0)
        ->and($summary['total_official'])->toEqual(0.0)
        ->and($summary['average_value'])->toEqual(0.0);
});

test('reportSummary respects the supplied report filters', function () {
    $school = School::factory()->create();
    makeReportRequest(['school_id' => $school->id], [['quantity' => 1, 'estimated' => 1000, 'official' => 1000]]);
    makeReportRequest([], [['quantity' => 1, 'estimated' => 999999, 'official' => 999999]]);

    $summary = ProcurementRequest::reportSummary(ProcurementRequest::bySchool($school->id));

    expect($summary['total_transactions'])->toBe(1)
        ->and($summary['total_official'])->toEqual(1000.0);
});

test('reportBySchool groups per school and orders by the biggest value', function () {
    $small = School::factory()->create(['name' => 'Sekolah Kecil']);
    $big = School::factory()->create(['name' => 'Sekolah Besar']);

    makeReportRequest(['school_id' => $small->id], [['quantity' => 1, 'estimated' => 10000, 'official' => 10000]]);
    makeReportRequest(['school_id' => $big->id], [['quantity' => 5, 'estimated' => 100000, 'official' => 100000]]);

    $rows = ProcurementRequest::reportBySchool(ProcurementRequest::query());

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['school_name'])->toBe('Sekolah Besar')
        ->and($rows[0]['total_transactions'])->toBe(1)
        ->and((float) $rows[0]['total_official'])->toEqual(500000.0)
        ->and((int) $rows[0]['total_quantity'])->toBe(5)
        ->and($rows[1]['school_name'])->toBe('Sekolah Kecil')
        ->and($rows[1]['school_npsn'])->toBeString();
});

test('reportBySupplier excludes requests that have no supplier yet', function () {
    $supplier = Supplier::factory()->create(['company_name' => 'CV Maju Jaya']);
    $other = Supplier::factory()->create(['company_name' => 'CV Sejahtera']);

    makeReportRequest(['supplier_id' => $supplier->id, 'status' => ProcurementRequest::STATUS_COMPLETED], [['quantity' => 1, 'estimated' => 20000, 'official' => 20000]]);
    makeReportRequest(['supplier_id' => $other->id], [['quantity' => 1, 'estimated' => 5000, 'official' => 5000]]);
    makeReportRequest(['supplier_id' => null], [['quantity' => 1, 'estimated' => 1000, 'official' => 1000]]);

    $rows = ProcurementRequest::reportBySupplier(ProcurementRequest::query());

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['company_name'])->toBe('CV Maju Jaya')
        ->and($rows[0]['completed_count'])->toBe(1)
        ->and((float) $rows[0]['total_official'])->toEqual(20000.0)
        ->and($rows[1]['completed_count'])->toBe(0);
});

test('reportByStatus zero fills every workflow status', function () {
    makeReportRequest(['status' => ProcurementRequest::STATUS_COMPLETED], [['quantity' => 1, 'estimated' => 7000, 'official' => 7000]]);

    $rows = ProcurementRequest::reportByStatus(ProcurementRequest::query());

    expect($rows)->toHaveCount(7)
        ->and($rows->pluck('status')->all())->toBe(ProcurementRequest::reportStatuses());

    $completed = $rows->firstWhere('status', ProcurementRequest::STATUS_COMPLETED);
    $draft = $rows->firstWhere('status', ProcurementRequest::STATUS_DRAFT);

    expect($completed['label'])->toBe('Selesai')
        ->and($completed['total_transactions'])->toBe(1)
        ->and((float) $completed['total_official'])->toEqual(7000.0)
        ->and($draft['total_transactions'])->toBe(0)
        ->and((float) $draft['total_official'])->toEqual(0.0);
});

test('reportTransactions returns mapped rows with relation labels', function () {
    $supplier = Supplier::factory()->create(['company_name' => 'CV Sumber Rejek']);
    $request = makeReportRequest(
        ['status' => ProcurementRequest::STATUS_COMPLETED, 'supplier_id' => $supplier->id],
        [['quantity' => 4, 'estimated' => 25000, 'official' => 20000]]
    );
    $request->forceFill(['grand_total' => 88000, 'totals_locked_at' => now()])->save();

    $rows = ProcurementRequest::reportTransactions(ProcurementRequest::query());

    expect($rows)->toHaveCount(1);

    $row = $rows->first();

    expect($row['id'])->toBe($request->id)
        ->and($row['uuid'])->toBe($request->uuid)
        ->and($row['supplier_name'])->toBe('CV Sumber Rejek')
        ->and($row['status_label'])->toBe('Selesai')
        ->and($row['total_quantity'])->toBe(4)
        ->and($row['total_item_types'])->toBe(1)
        ->and((float) $row['total_estimated'])->toEqual(100000.0)
        ->and((float) $row['total_official'])->toEqual(88000.0)
        ->and($row['school_name'])->toBeString()
        ->and($row['category_name'])->toBeString()
        ->and($row['budget_year_name'])->toBeString();
});

test('reportTransactions leaves supplier blank when none is assigned', function () {
    makeReportRequest(['supplier_id' => null]);

    expect(ProcurementRequest::reportTransactions(ProcurementRequest::query())->first()['supplier_name'])->toBeNull();
});

test('reportTransactions honours the limit and the report filters', function () {
    $school = School::factory()->create();
    makeReportRequest(['school_id' => $school->id]);
    makeReportRequest(['school_id' => $school->id]);
    makeReportRequest(['school_id' => School::factory()->create()->id]);

    expect(ProcurementRequest::reportTransactions(ProcurementRequest::bySchool($school->id)))->toHaveCount(2)
        ->and(ProcurementRequest::reportTransactions(ProcurementRequest::query(), limit: 1))->toHaveCount(1);
});

test('soft deleted requests are excluded from every report', function () {
    $request = makeReportRequest([], [['quantity' => 1, 'estimated' => 1000, 'official' => 1000]]);
    $request->delete();

    expect(ProcurementRequest::reportSummary(ProcurementRequest::query())['total_transactions'])->toBe(0)
        ->and(ProcurementRequest::reportTransactions(ProcurementRequest::query()))->toHaveCount(0)
        ->and(ProcurementRequest::reportBySchool(ProcurementRequest::query()))->toHaveCount(0)
        ->and(ProcurementRequest::reportBySupplier(ProcurementRequest::query()))->toHaveCount(0);
});

test('report base subtotal stays on estimates when the only official price is zero', function () {
    $request = makeReportRequest([], [
        ['quantity' => 1, 'estimated' => 100000, 'official' => 0],
    ]);

    $row = ProcurementRequest::withReportTotals()->findOrFail($request->id);

    expect((float) $row->report_base_subtotal)->toEqual(100000.0)
        ->and((float) $row->report_estimated_subtotal)->toEqual(100000.0);
});

test('report base subtotal sums official and estimated when official prices are partial', function () {
    $request = makeReportRequest([], [
        ['quantity' => 2, 'estimated' => 100000, 'official' => 90000],
        ['quantity' => 1, 'estimated' => 50000, 'official' => null],
    ]);

    $row = ProcurementRequest::withReportTotals()->findOrFail($request->id);

    expect((float) $row->report_base_subtotal)->toEqual(230000.0)
        ->and((float) $row->report_effective_total)->toEqual(230000.0);
});

test('report_effective_total ignores grand_total when totals are not locked', function () {
    $request = makeReportRequest(
        ['is_taxable' => true, 'ppn_rate' => 10],
        [['quantity' => 1, 'estimated' => 100000, 'official' => 90000]]
    );

    $request->forceFill([
        'grand_total' => 123456,
        'totals_locked_at' => null,
    ])->save();

    $row = ProcurementRequest::withReportTotals()->findOrFail($request->id);

    expect((float) $row->report_effective_total)->toEqual(99000.0);
});

test('reportTransactions keeps showing a soft deleted school and supplier', function () {
    $school = School::factory()->create(['name' => 'Sekolah Lama']);
    $supplier = Supplier::factory()->create(['company_name' => 'CV Lama']);

    makeReportRequest([
        'school_id' => $school->id,
        'supplier_id' => $supplier->id,
    ], [['quantity' => 1, 'estimated' => 10000, 'official' => 10000]]);

    $school->delete();
    $supplier->delete();

    $rows = ProcurementRequest::reportTransactions(ProcurementRequest::query());

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['school_name'])->toBe('Sekolah Lama')
        ->and($rows->first()['supplier_name'])->toBe('CV Lama');
});

test('scopePeriod interprets the bounds in the report timezone', function () {
    $tz = config('report.timezone', 'Asia/Jakarta');

    $before = makeReportRequest(['requested_at' => Carbon::parse('2026-06-14 20:00:00', $tz)->utc()]);
    $inside = makeReportRequest(['requested_at' => Carbon::parse('2026-06-15 08:00:00', $tz)->utc()]);
    $after = makeReportRequest(['requested_at' => Carbon::parse('2026-06-16 02:00:00', $tz)->utc()]);

    $result = ProcurementRequest::period('2026-06-15', '2026-06-15')->pluck('id')->all();

    expect($result)->toBe([$inside->id])
        ->and($result)->not->toContain($before->id)
        ->and($result)->not->toContain($after->id);
});
