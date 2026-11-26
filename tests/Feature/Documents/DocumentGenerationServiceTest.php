<?php

use App\Models\GeneratedDocument;
use App\Models\ProcurementRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake();
    Storage::fake('local');
    Storage::fake('public');
});

test('downloadUrl returns the stored local file path', function () {
    Storage::fake('local');
    $procurement = ProcurementRequest::factory()->create();

    Storage::disk('local')->put('temp/cover-'.$procurement->uuid.'.pdf', '%PDF-fake');

    $document = GeneratedDocument::create([
        'procurement_request_id' => $procurement->id,
        'document_type' => 'cover',
        'file_path' => 'temp/cover-'.$procurement->uuid.'.pdf',
        'download_token' => Str::random(64),
    ]);

    expect(file_exists($document->downloadUrl()))->toBeTrue();
});
