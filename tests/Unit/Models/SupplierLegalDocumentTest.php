<?php

use App\Models\Supplier;
use App\Models\SupplierLegalDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('SupplierLegalDocument Model', function () {
    it('can create a document', function () {
        $doc = SupplierLegalDocument::factory()->create();

        expect($doc)->toBeInstanceOf(SupplierLegalDocument::class)
            ->and($doc->exists)->toBeTrue();
    });

    it('belongs to a supplier', function () {
        $doc = SupplierLegalDocument::factory()->create();

        expect($doc->supplier)->toBeInstanceOf(Supplier::class);
    });

    describe('Business Methods', function () {
        it('isExpired returns false when valid_until is null (permanent)', function () {
            $doc = SupplierLegalDocument::factory()->permanent()->create();

            expect($doc->isExpired())->toBeFalse();
        });

        it('isExpired returns false when valid_until is in future', function () {
            $doc = SupplierLegalDocument::factory()->create([
                'valid_until' => now()->addMonth(),
            ]);

            expect($doc->isExpired())->toBeFalse();
        });

        it('isExpired returns true when valid_until is in past', function () {
            $doc = SupplierLegalDocument::factory()->expired()->create();

            expect($doc->isExpired())->toBeTrue();
        });

        it('isBusinessPermit returns true for business_permit type', function () {
            $doc = SupplierLegalDocument::factory()->businessPermit()->create();

            expect($doc->isBusinessPermit())->toBeTrue();
        });

        it('isBusinessPermit returns false for other types', function () {
            $doc = SupplierLegalDocument::factory()->deed()->create();

            expect($doc->isBusinessPermit())->toBeFalse();
        });

        it('isDeed returns true for deed type', function () {
            $doc = SupplierLegalDocument::factory()->deed()->create();

            expect($doc->isDeed())->toBeTrue();
        });

        it('isDeed returns false for other types', function () {
            $doc = SupplierLegalDocument::factory()->businessPermit()->create();

            expect($doc->isDeed())->toBeFalse();
        });
    });
});