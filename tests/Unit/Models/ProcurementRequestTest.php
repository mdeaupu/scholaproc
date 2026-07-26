<?php

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Models\BudgetYear;
use App\Models\FundingSource;
use App\Models\PackageCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it automatically generates a uuid when creating a procurement request', function () {
    $request = ProcurementRequest::factory()->create(['uuid' => null]);

    expect($request->uuid)->not->toBeNull()
        ->and($request->uuid)->toBeString()
        ->and(strlen($request->uuid))->toBe(36);
});

test('it belongs to master models correctly', function () {
    $request = ProcurementRequest::factory()->create();

    expect($request->school)->toBeInstanceOf(School::class)
        ->and($request->budgetYear)->toBeInstanceOf(BudgetYear::class)
        ->and($request->fundingSource)->toBeInstanceOf(FundingSource::class)
        ->and($request->packageCategory)->toBeInstanceOf(PackageCategory::class);
});

test('it can calculate total estimated and official amounts correctly from items', function () {
    $request = ProcurementRequest::factory()->create();

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'quantity' => 2,
        'estimated_price' => 50000,
        'official_price' => 45000,
    ]);

    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'quantity' => 1,
        'estimated_price' => 100000,
        'official_price' => 90000,
    ]);

    expect(ProcurementRequest::getTotalEstimatedAmount())->toBe(200000.0)
        ->and(ProcurementRequest::getTotalOfficialAmount())->toBe(180000.0);
});