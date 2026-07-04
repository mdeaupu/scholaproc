<?php

use App\Models\ProcurementRequestItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

require_once __DIR__ . '/ProcurementTestHelpers.php';

describe('ProcurementRequest - kalkulasi subtotal & pajak', function () {

    it('menghitung estimatedSubtotal dari quantity x estimated_price semua item', function () {
        $procurement = readyProcurement();

        expect($procurement->estimatedSubtotal())->toBe(7_900_000.0);
    });

    it('menghitung officialSubtotal dari quantity x official_price semua item', function () {
        $procurement = readyProcurement();

        expect($procurement->officialSubtotal())->toBe(7_200_000.0);
    });

    it('totalPpn dihitung dari base resmi (official) jika sudah ada, bukan estimasi', function () {
        $procurement = readyProcurement();

        expect($procurement->totalPpn())->toEqualWithDelta(792_000.0, 0.01);
    });

    it('totalPpn = 0 kalau is_taxable = false, walau ppn_rate > 0', function () {
        $procurement = readyProcurement(['is_taxable' => false]);

        expect($procurement->totalPpn())->toBe(0.0);
    });

    it('totalPph22 dan totalPph23 HANYA menjumlahkan item dengan is_pph = true', function () {
        $procurement = readyProcurement();

        expect($procurement->totalPph22())->toEqualWithDelta(75_000.0, 0.01)
            ->and($procurement->totalPph23())->toEqualWithDelta(100_000.0, 0.01);
    });

    it('grandTotal = base subtotal + PPN (belum dikurangi PPh)', function () {
        $procurement = readyProcurement();

        expect($procurement->grandTotal())->toEqualWithDelta(7_992_000.0, 0.01);
    });

    it('REGRESI: netTotal harus MENGURANGI PPh22 & PPh23 dari grandTotal, bukan menambahkannya', function () {
        $procurement = readyProcurement();

        expect($procurement->netTotal())->toEqualWithDelta(7_817_000.0, 0.01)
            ->and($procurement->netTotal())
            ->toBeLessThan($procurement->grandTotal());
    });

});

describe('ProcurementRequest - kesiapan generate dokumen', function () {

    it('isReadyForDocumentGeneration = true kalau supplier, signatory, dan harga resmi lengkap', function () {
        $procurement = readyProcurement();

        expect($procurement->isReadyForDocumentGeneration())->toBeTrue();
    });

    it('isReadyForDocumentGeneration = false kalau belum ada supplier', function () {
        $procurement = readyProcurement(['supplier_id' => null]);

        expect($procurement->hasSupplier())->toBeFalse()
            ->and($procurement->isReadyForDocumentGeneration())->toBeFalse();
    });

    it('isReadyForDocumentGeneration = false kalau signatory belum lengkap (treasurer belum ada)', function () {
        $procurement = readyProcurement();
        $procurement->signatories()->where('role', 'treasurer')->delete();
        $procurement->refresh();

        expect($procurement->hasSignatories())->toBeFalse()
            ->and($procurement->isReadyForDocumentGeneration())->toBeFalse();
    });

    it('isReadyForDocumentGeneration = false kalau ada item tanpa official_price', function () {
        $procurement = readyProcurement();

        /** @var ProcurementRequestItem $item */
        $item = $procurement->items()->first();
        $item->update(['official_price' => null]);

        $procurement->refresh();

        expect($procurement->hasOfficialPrices())->toBeFalse()
            ->and($procurement->isReadyForDocumentGeneration())->toBeFalse();
    });

});

describe('ProcurementRequest - generateOfficialDocuments', function () {

    it('membuat 8 baris procurement_documents dengan nomor & tanggal terisi', function () {
        $procurement = readyProcurement();

        $procurement->generateOfficialDocuments();
        $procurement->refresh();

        expect($procurement->documents)->toHaveCount(8);

        $types = $procurement->documents->pluck('document_type')->sort()->values()->all();

        $expected = ['cover', 'planning', 'negotiation', 'purchase_order', 'inspection', 'bast', 'invoice', 'receipt'];
        sort($expected);

        expect($types)->toBe($expected);

        $procurement->documents->each(function ($doc) {
            expect($doc->document_number)->not->toBeEmpty()
                ->and($doc->document_date)->not->toBeNull();
        });
    });

    it('melempar Exception kalau dipanggil sebelum data siap', function () {
        $procurement = readyProcurement(['supplier_id' => null]);

        expect(fn() => $procurement->generateOfficialDocuments())
            ->toThrow(Exception::class);
    });

});
