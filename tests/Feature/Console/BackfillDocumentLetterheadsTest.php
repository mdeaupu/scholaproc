<?php

use App\Models\ProcurementDocument;
use App\Models\ProcurementRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('backfill embeds stored letterhead data in historical document snapshots', function () {
    Storage::fake('public');
    $path = UploadedFile::fake()->image('kop.png')->store('subsidiary-letterheads', 'public');
    $contents = Storage::disk('public')->get($path);

    $request = ProcurementRequest::factory()->create([
        'subsidiary_snapshot' => ['letterhead_path' => $path],
    ]);
    $document = ProcurementDocument::factory()->create([
        'procurement_request_id' => $request->id,
        'document_snapshot' => [
            'document_type' => 'purchase_order',
            'subsidiary' => ['name' => 'PT Snapshot Lama'],
        ],
    ]);
    DB::table('procurement_documents')
        ->where('id', $document->id)
        ->update(['issued_at' => now()]);

    $exitCode = Artisan::call('documents:backfill-letterheads');
    $snapshot = $document->fresh()->document_snapshot;

    expect($exitCode)->toBe(0)
        ->and(data_get($snapshot, 'subsidiary.letterhead_base64'))->toBe(base64_encode($contents))
        ->and(data_get($snapshot, 'subsidiary.letterhead_mime_type'))->toBe('image/png')
        ->and(data_get($snapshot, 'subsidiary.letterhead_path'))->toBe($path)
        ->and(Artisan::output())->toContain('Backfilled 1 document snapshots');
});

test('backfill copies frozen base64 from the subsidiary snapshot when its file is gone', function () {
    $encoded = base64_encode('historical-letterhead-bytes');
    $document = ProcurementDocument::factory()->create([
        'document_snapshot' => ['subsidiary' => ['name' => 'PT Snapshot Lama']],
        'subsidiary_snapshot' => [
            'letterhead_base64' => $encoded,
            'letterhead_mime_type' => 'image/png',
        ],
    ]);

    $exitCode = Artisan::call('documents:backfill-letterheads');
    $snapshot = $document->fresh()->document_snapshot;

    expect($exitCode)->toBe(0)
        ->and(data_get($snapshot, 'subsidiary.letterhead_base64'))->toBe($encoded)
        ->and(data_get($snapshot, 'subsidiary.letterhead_mime_type'))->toBe('image/png');
});

test('backfill reports documents whose letterhead cannot be found', function () {
    ProcurementDocument::factory()->create([
        'document_snapshot' => [
            'subsidiary' => ['letterhead_path' => 'missing/kop.png'],
        ],
    ]);

    $exitCode = Artisan::call('documents:backfill-letterheads');

    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('Could not locate a valid stored letterhead');
});
