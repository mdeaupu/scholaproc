<?php

use App\Models\GeneratedDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

require_once __DIR__ . '/ProcurementTestHelpers.php';

beforeEach(function () {
    Storage::fake();
    Storage::fake('local');
    Storage::fake('public');
});

describe('DocumentGenerationService - validasi kesiapan', function () {

    it('melempar Exception kalau procurement belum siap generate', function () {
        $procurement = readyProcurement(['supplier_id' => null]);

        expect(fn() => $procurement->generateCover())->toThrow(Exception::class);
    });

});

describe('DocumentGenerationService - nomor dokumen resmi', function () {

    it('otomatis membuat nomor dokumen resmi kalau procurement_documents belum lengkap', function () {
        $procurement = readyProcurement();

        expect($procurement->documents)->toHaveCount(0);

        $procurement->generateCover();

        expect($procurement->fresh()->documents)->toHaveCount(9);
    });

    it('TIDAK menomori ulang dokumen yang sudah lengkap (idempoten kalau sudah 9)', function () {
        $procurement = readyProcurement();
        $procurement->generateOfficialDocuments();
        $procurement->refresh();

        $originalNumbers = $procurement->documents->pluck('document_number', 'document_type')->all();

        $procurement->generateCover();

        $numbersAfter = $procurement->fresh('documents')->documents
            ->pluck('document_number', 'document_type')->all();

        expect($numbersAfter)->toBe($originalNumbers);
    });

});

describe('DocumentGenerationService - generate tiap tipe dokumen (regresi bug nama view)', function () {

    $types = [
        'cover' => 'generateCover',
        'planning' => 'generatePlanning',
        'negotiation' => 'generateNegotiation',
        'purchase_order' => 'generatePurchaseOrder',
        'inspection' => 'generateInspection',
        'bast' => 'generateBast',
        'invoice' => 'generateInvoice',
        'receipt' => 'generateReceipt',
        'supplier_declaration' => 'generateSupplierDeclaration',
    ];

    foreach ($types as $type => $method) {
        it("berhasil generate dokumen tipe '{$type}' tanpa error (view ditemukan & PDF ter-render)", function () use ($type, $method) {
            $procurement = readyProcurement();

            /** @var GeneratedDocument $document */
            $document = $procurement->$method();

            expect($document)->toBeInstanceOf(GeneratedDocument::class)
                ->and($document->document_type)->toBe($type)
                ->and($document->download_token)->not->toBeEmpty()
                ->and($document->procurement_request_id)->toBe($procurement->id);
        });
    }

    it('generateAllDocuments membuat 9 GeneratedDocument sekaligus', function () {
        $procurement = readyProcurement();

        $procurement->generateAllDocuments();

        expect(GeneratedDocument::where('procurement_request_id', $procurement->id)->count())->toBe(9);

        $types = GeneratedDocument::where('procurement_request_id', $procurement->id)
            ->pluck('document_type')->sort()->values()->all();

        $expected = collect(['cover', 'planning', 'negotiation', 'purchase_order', 'inspection', 'bast', 'invoice', 'receipt', 'supplier_declaration'])
            ->sort()->values()->all();

        expect($types)->toBe($expected);
    });

});

describe('DocumentGenerationService - regeneratePdf', function () {

    it('membuat download_token baru dan menghapus record lama', function () {
        $procurement = readyProcurement();
        $document = $procurement->generateCover();
        $oldId = $document->id;
        $oldToken = $document->download_token;

        $regenerated = $document->regeneratePdf();

        expect($regenerated->download_token)->not->toBe($oldToken)
            ->and(GeneratedDocument::find($oldId))->toBeNull()
            ->and($regenerated->document_type)->toBe('cover');
    });

});

describe('DocumentGenerationService - downloadUrl', function () {

    it('mengembalikan path file yang benar-benar ada', function () {
        $procurement = readyProcurement();
        $document = $procurement->generateCover();

        expect(file_exists($document->downloadUrl()))->toBeTrue();
    });

});
