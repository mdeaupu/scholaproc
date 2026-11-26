<?php

use App\Models\GeneratedDocument;
use App\Models\ProcurementRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

require_once __DIR__.'/ProcurementTestHelpers.php';

beforeEach(function () {
    Storage::fake();
    Storage::fake('local');
    Storage::fake('public');
});

describe('DocumentController - download()', function () {

    it('bisa download PDF lewat download_token', function () {
        $user = makeUser();
        $procurement = ProcurementRequest::factory()->create();
        Storage::disk('local')->put('temp/cover-'.$procurement->uuid.'.pdf', '%PDF-fake');

        $document = GeneratedDocument::create([
            'procurement_request_id' => $procurement->id,
            'document_type' => 'cover',
            'file_path' => 'temp/cover-'.$procurement->uuid.'.pdf',
            'download_token' => Str::random(64),
        ]);

        $response = $this->actingAs($user)->get(route('documents.download', $document->download_token));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    });

    it('download_token yang tidak ada mengembalikan 404', function () {
        $user = makeUser();

        $response = $this->actingAs($user)->get(route('documents.download', 'token-tidak-ada'));

        $response->assertNotFound();
    });

});
