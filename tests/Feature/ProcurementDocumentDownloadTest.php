<?php

use App\Models\ProcurementDocument;
use App\Models\ProcurementRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('purchase order download uses a stable English filename', function () {
    Storage::fake('public');
    $letterheadPath = UploadedFile::fake()->image('kop.png')->store('subsidiary-letterheads', 'public');
    $letterheadBase64 = base64_encode(Storage::disk('public')->get($letterheadPath));
    $identity = [
        'name' => 'PT Contoh',
        'npwp' => '123456789012345',
        'director_name' => 'Direktur Contoh',
        'letterhead_path' => $letterheadPath,
        'letterhead_url' => Storage::disk('public')->url($letterheadPath),
        'letterhead_base64' => $letterheadBase64,
        'letterhead_mime_type' => 'image/png',
    ];
    $admin = User::factory()->admin()->create();
    $request = ProcurementRequest::factory()->create();
    $document = ProcurementDocument::factory()->create([
        'procurement_request_id' => $request->id,
        'document_type' => 'purchase_order',
        'document_number' => 'SP/123/2026',
        'document_snapshot' => [
            'subsidiary' => $identity,
            'school' => [],
            'transaction' => [],
        ],
        'subsidiary_snapshot' => $identity,
    ]);
    DB::table('procurement_documents')
        ->where('id', $document->id)
        ->update(['issued_at' => now()]);

    $this->actingAs($admin)
        ->get(route('procurement.documents.download', [$request, $document]))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=purchase_order_'.$document->id.'.pdf');
});
