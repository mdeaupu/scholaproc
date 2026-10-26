<?php

use App\Models\Subsidiary;
use App\Models\SubsidiaryLegalDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('SubsidiaryLegalDocument model', function () {
    it('belongs to a subsidiary', function () {
        $document = SubsidiaryLegalDocument::factory()->create();

        expect($document->subsidiary)->toBeInstanceOf(Subsidiary::class);
    });

    it('detects expired and permanent documents', function () {
        $expired = SubsidiaryLegalDocument::factory()->expired()->create();
        $permanent = SubsidiaryLegalDocument::factory()->permanent()->create();

        expect($expired->isExpired())->toBeTrue()
            ->and($permanent->isExpired())->toBeFalse();
    });
});
