<?php

use App\Models\ProcurementDocument;
use App\Models\ProcurementRequest;
use App\Models\Supplier;
use App\Services\ProcurementNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('isComplete mengecek status kelengkapan dokumen', function () {
    $document = ProcurementDocument::factory()->create([
        'document_number' => null,
        'document_date' => null,
    ]);

    expect($document->isComplete())->toBeFalse();

    $document->update([
        'document_number' => 'DOC-2026/06/1234',
        'document_date' => now(),
    ]);

    expect($document->isComplete())->toBeTrue();
});

test('ProcurementNumberGenerator men-generate nomor dokumen otomatis', function () {
    $supplier = Supplier::factory()->create();
    $request = ProcurementRequest::factory()->create([
        'supplier_id' => $supplier->id,
    ]);

    $generator = app(ProcurementNumberGenerator::class);
    $generatedNumber = $generator->generate($request, 'bast');

    expect($generatedNumber)->not->toBeEmpty();

    $currentYear = date('Y');
    expect($generatedNumber)
        ->toContain($currentYear)
        ->toContain('BAST');
});