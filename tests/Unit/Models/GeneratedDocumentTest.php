<?php

use App\Models\GeneratedDocument;
use App\Models\ProcurementRequest;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('GeneratedDocument', function () {

    it('punya relasi procurementRequest (BelongsTo)', function () {
        $document = GeneratedDocument::factory()->create();

        expect($document->procurementRequest)->toBeInstanceOf(ProcurementRequest::class);
    });

    it('download_token bersifat unik di database', function () {
        $token = Str::random(64);
        GeneratedDocument::factory()->create(['download_token' => $token]);

        expect(fn() => GeneratedDocument::factory()->create(['download_token' => $token]))
            ->toThrow(QueryException::class);
    });

    it('forType() state mengubah document_type sesuai permintaan', function () {
        $document = GeneratedDocument::factory()->forType('invoice')->create();

        expect($document->document_type)->toBe('invoice');
    });

});
