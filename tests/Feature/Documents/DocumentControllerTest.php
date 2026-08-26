<?php

use App\Models\GeneratedDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

require_once __DIR__ . '/ProcurementTestHelpers.php';

beforeEach(function () {
    Storage::fake();
    Storage::fake('local');
    Storage::fake('public');
});

describe('DocumentController - auth guard', function () {

    it('redirect ke login kalau belum login', function () {
        $procurement = readyProcurement();

        $response = $this->get(route('documents.generate', [
            'procurement' => $procurement->id,
            'type' => 'cover',
        ]));

        $response->assertRedirect(route('login'));
    });

});

describe('DocumentController - generate()', function () {

    it('user login bisa generate satu dokumen dan menerima file PDF', function () {
        $user = makeUser();
        $procurement = readyProcurement();

        $response = $this->actingAs($user)->get(route('documents.generate', [
            'procurement' => $procurement->id,
            'type' => 'cover',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        expect(GeneratedDocument::where('procurement_request_id', $procurement->id)
            ->where('document_type', 'cover')->exists())->toBeTrue();
    });

    it('type=all men-generate 9 dokumen sekaligus dan redirect back dengan pesan sukses', function () {
        $user = makeUser();
        $procurement = readyProcurement();

        $response = $this->actingAs($user)->get(route('documents.generate', [
            'procurement' => $procurement->id,
            'type' => 'all',
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        expect(GeneratedDocument::where('procurement_request_id', $procurement->id)->count())->toBe(9);
    });

    it('tipe dokumen tidak valid mengembalikan 404', function () {
        $user = makeUser();
        $procurement = readyProcurement();

        $response = $this->actingAs($user)->get(route('documents.generate', [
            'procurement' => $procurement->id,
            'type' => 'tipe-ngasal',
        ]));

        $response->assertNotFound();
    });

    it('procurement yang belum siap redirect back dengan pesan error, BUKAN error 500', function () {
        $user = makeUser();
        $procurement = readyProcurement(['supplier_id' => null]);

        $response = $this->actingAs($user)->get(route('documents.generate', [
            'procurement' => $procurement->id,
            'type' => 'cover',
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        expect(GeneratedDocument::where('procurement_request_id', $procurement->id)->exists())->toBeFalse();
    });

});

describe('DocumentController - download()', function () {

    it('bisa download PDF lewat download_token', function () {
        $user = makeUser();
        $procurement = readyProcurement();
        $document = $procurement->generateCover();

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

describe('DocumentController - regenerate()', function () {

    it('bisa regenerate dokumen dan redirect back dengan pesan sukses', function () {
        $user = makeUser();
        $procurement = readyProcurement();
        $document = $procurement->generateCover();

        $response = $this->actingAs($user)->post(route('documents.regenerate', $document->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        expect(GeneratedDocument::find($document->id))->toBeNull();
        expect(GeneratedDocument::where('procurement_request_id', $procurement->id)
            ->where('document_type', 'cover')->exists())->toBeTrue();
    });

});
