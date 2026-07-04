<?php

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ProcurementRequestHistory', function () {

    it('created_at otomatis terisi walau timestamps model dimatikan', function () {
        $history = ProcurementRequestHistory::factory()->create();

        expect($history->created_at)->not->toBeNull()
            ->and($history->updated_at)->toBeNull();
    });

    it('relasi user() dan createdBy() mengarah ke User yang sama', function () {
        $user = User::factory()->create();
        $history = ProcurementRequestHistory::factory()->create(['user_id' => $user->id]);

        expect($history->user->id)->toBe($user->id)
            ->and($history->createdBy->id)->toBe($user->id);
    });

    it('punya relasi procurementRequest (BelongsTo)', function () {
        $procurement = ProcurementRequest::factory()->create();
        $history = ProcurementRequestHistory::factory()->create([
            'procurement_request_id' => $procurement->id,
        ]);

        expect($history->procurementRequest->id)->toBe($procurement->id);
    });

    it('recordHistory() di ProcurementRequest membuat baris histori baru', function () {
        $procurement = ProcurementRequest::factory()->create();
        $user = User::factory()->create();

        $procurement->recordHistory($user, 'submitted', 'Diajukan oleh sekolah');

        expect($procurement->histories()->count())->toBe(1)
            ->and($procurement->histories()->first()->notes)->toBe('Diajukan oleh sekolah');
    });

});
