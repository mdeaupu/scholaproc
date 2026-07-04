<?php

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ProcurementRequestItem - kalkulasi jumlah', function () {

    it('estimatedAmount() = quantity x estimated_price', function () {
        $item = ProcurementRequestItem::factory()->create([
            'quantity' => 5,
            'estimated_price' => 60000,
            'official_price' => null,
        ]);

        expect($item->estimatedAmount())->toBe(300_000.0);
    });

    it('officialAmount() = 0 kalau official_price masih null', function () {
        $item = ProcurementRequestItem::factory()->create([
            'quantity' => 5,
            'estimated_price' => 60000,
            'official_price' => null,
        ]);

        expect($item->officialAmount())->toBe(0.0);
    });

    it('officialAmount() = quantity x official_price kalau sudah terisi', function () {
        $item = ProcurementRequestItem::factory()->create([
            'quantity' => 5,
            'estimated_price' => 60000,
            'official_price' => 55000,
        ]);

        expect($item->officialAmount())->toBe(275_000.0);
    });

    it('pphAmount() = 0 kalau is_pph = false, walau ada rate PPh di procurement', function () {
        $procurement = ProcurementRequest::factory()->create([
            'pph_22_rate' => 1.5,
            'pph_23_rate' => 2.0,
        ]);

        $item = ProcurementRequestItem::factory()->create([
            'procurement_request_id' => $procurement->id,
            'quantity' => 100,
            'estimated_price' => 50000,
            'official_price' => 50000,
            'is_pph' => false,
        ]);

        expect($item->pphAmount())->toBe(0.0);
    });

    it('pphAmount() dihitung dari gabungan pph_22_rate + pph_23_rate procurement induknya', function () {
        $procurement = ProcurementRequest::factory()->create([
            'pph_22_rate' => 1.5,
            'pph_23_rate' => 2.0,
        ]);

        $item = ProcurementRequestItem::factory()->create([
            'procurement_request_id' => $procurement->id,
            'quantity' => 100,
            'estimated_price' => 55000,
            'official_price' => 50000,
            'is_pph' => true,
        ]);

        expect($item->pphAmount())->toEqualWithDelta(175_000.0, 0.01);
    });

    it('accessor estimated_amount_total dan official_amount_total konsisten dengan method-nya', function () {
        $item = ProcurementRequestItem::factory()->create([
            'quantity' => 10,
            'estimated_price' => 25000,
            'official_price' => 20000,
        ]);

        expect($item->estimated_amount_total)->toBe($item->estimatedAmount())
            ->and($item->official_amount_total)->toBe($item->officialAmount());
    });

    it('cast quantity integer dan is_pph boolean', function () {
        $item = ProcurementRequestItem::factory()->create([
            'quantity' => 10,
            'is_pph' => true,
        ]);

        expect($item->quantity)->toBeInt()
            ->and($item->is_pph)->toBeBool();
    });

    it('punya relasi procurementRequest (BelongsTo)', function () {
        $procurement = ProcurementRequest::factory()->create();
        $item = ProcurementRequestItem::factory()->create([
            'procurement_request_id' => $procurement->id,
        ]);

        expect($item->procurementRequest->id)->toBe($procurement->id);
    });

});
