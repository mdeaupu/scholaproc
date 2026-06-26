<?php

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\Supplier;
use App\Models\SupplierLegalDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Supplier Model', function () {
    it('can create a supplier', function () {
        $supplier = Supplier::factory()->create();

        expect($supplier)->toBeInstanceOf(Supplier::class)
            ->and($supplier->exists)->toBeTrue();
    });

    it('has legalDocuments relationship', function () {
        $supplier = Supplier::factory()->create();
        $doc = SupplierLegalDocument::factory()->for($supplier)->create();

        expect($supplier->legalDocuments)->toHaveCount(1)
            ->and($supplier->legalDocuments->first()->id)->toBe($doc->id);
    });

    it('has procurementRequests relationship', function () {
        $supplier = Supplier::factory()->create();
        $pr = ProcurementRequest::factory()->for($supplier)->create();

        expect($supplier->procurementRequests)->toHaveCount(1)
            ->and($supplier->procurementRequests->first()->id)->toBe($pr->id);
    });

    describe('Business Methods', function () {
        it('activeBusinessPermit returns active permit', function () {
            $supplier = Supplier::factory()->create();
            $activePermit = SupplierLegalDocument::factory()
                ->for($supplier)
                ->businessPermit()
                ->create(['valid_until' => now()->addMonth()]);

            SupplierLegalDocument::factory()
                ->for($supplier)
                ->businessPermit()
                ->expired()
                ->create();

            $result = $supplier->activeBusinessPermit();

            expect($result)->toBeInstanceOf(SupplierLegalDocument::class)
                ->and($result->id)->toBe($activePermit->id);
        });

        it('activeBusinessPermit returns null if no active permit', function () {
            $supplier = Supplier::factory()->create();

            SupplierLegalDocument::factory()
                ->for($supplier)
                ->businessPermit()
                ->expired()
                ->create();

            $result = $supplier->activeBusinessPermit();

            expect($result)->toBeNull();
        });

        it('hasCompleteLegalDocuments returns true when both deed and permit exist', function () {
            $supplier = Supplier::factory()->create();
            SupplierLegalDocument::factory()->for($supplier)->deed()->create();
            SupplierLegalDocument::factory()->for($supplier)->businessPermit()->create();

            expect($supplier->hasCompleteLegalDocuments())->toBeTrue();
        });

        it('hasCompleteLegalDocuments returns false when missing deed', function () {
            $supplier = Supplier::factory()->create();
            SupplierLegalDocument::factory()->for($supplier)->businessPermit()->create();

            expect($supplier->hasCompleteLegalDocuments())->toBeFalse();
        });

        it('hasCompleteLegalDocuments returns false when missing permit', function () {
            $supplier = Supplier::factory()->create();
            SupplierLegalDocument::factory()->for($supplier)->deed()->create();

            expect($supplier->hasCompleteLegalDocuments())->toBeFalse();
        });

        it('totalProjects returns correct count', function () {
            $supplier = Supplier::factory()->create();
            ProcurementRequest::factory()->for($supplier)->count(3)->create();

            expect($supplier->totalProjects())->toBe(3);
        });

        it('totalProjectValue returns sum of all item prices', function () {
            $supplier = Supplier::factory()->create();

            $pr1 = ProcurementRequest::factory()->for($supplier)->create();
            ProcurementRequestItem::factory()->for($pr1)->create([
                'quantity' => 5,
                'estimated_price' => 200000,
                'official_price' => 200000,
            ]);

            $pr2 = ProcurementRequest::factory()->for($supplier)->create();
            ProcurementRequestItem::factory()->for($pr2)->create([
                'quantity' => 3,
                'estimated_price' => 150000,
                'official_price' => 150000,
            ]);

            expect($supplier->totalProjectValue())->toBe(1450000.0);
        });

        it('totalProjectValue returns 0 if no procurement', function () {
            $supplier = Supplier::factory()->create();

            expect($supplier->totalProjectValue())->toBe(0);
        });
    });

    it('soft deletes cascade to legal documents', function () {
        $supplier = Supplier::factory()->create();
        $doc = SupplierLegalDocument::factory()->for($supplier)->create();

        $supplier->delete();

        expect($supplier->trashed())->toBeTrue()
            ->and($doc->fresh())->toBeNull();
    });
});