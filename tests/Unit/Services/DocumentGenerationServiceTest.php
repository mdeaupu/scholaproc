<?php

use App\Models\ProcurementDocument;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\Subsidiary;
use App\Models\User;
use App\Services\DocumentGenerationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('document templates are selected by official M8 document type', function () {
    $service = app(DocumentGenerationService::class);

    expect($service->viewFor(new ProcurementDocument(['document_type' => 'purchase_order'])))
        ->toBe('documents.surat-pesanan')
        ->and($service->viewFor(new ProcurementDocument(['document_type' => 'bast'])))
        ->toBe('documents.bast')
        ->and($service->viewFor(new ProcurementDocument(['document_type' => 'invoice'])))
        ->toBe('documents.invoice')
        ->and($service->viewFor(new ProcurementDocument(['document_type' => 'delivery'])))
        ->toBeNull();
});

test('bast view receives the actual letterhead and identity from the issued snapshot', function () {
    Storage::fake('public');
    $path = UploadedFile::fake()->image('kop.png')->store('subsidiary-letterheads', 'public');
    $identity = [
        'name' => 'PT Snapshot Lama',
        'npwp' => '123456789012345',
        'director_name' => 'Direktur Snapshot',
        'address' => 'Alamat snapshot',
        'letterhead_path' => $path,
        'letterhead_url' => Storage::disk('public')->url($path),
    ];
    $document = new ProcurementDocument([
        'document_type' => 'bast',
        'document_number' => 'BAST/123/2026',
        'document_date' => now()->toDateString(),
        'document_snapshot' => [
            'subsidiary' => $identity,
            'school' => ['name' => 'Sekolah Contoh'],
            'stage' => [
                'received_at' => '2026-11-02',
                'purchase_order' => [
                    'number' => 'SP/123/2026',
                    'date' => '2026-10-28',
                ],
                'signatories' => [[
                    'role' => 'headmaster',
                    'name' => 'Kepala Sekolah Snapshot',
                    'nip' => '198001012000011001',
                    'title' => 'Kepala Sekolah',
                ]],
                'items' => [[
                    'item' => 'Laptop',
                    'quantity' => 2,
                    'condition' => 'good',
                ]],
            ],
        ],
        'subsidiary_snapshot' => $identity,
    ]);

    $service = app(DocumentGenerationService::class);
    $data = $service->viewData($document);
    $html = view($service->viewFor($document), $data)->render();

    expect($data['letterhead']['path'])->toBe($path)
        ->and($data['letterhead']['url'])->toBe($identity['letterhead_url'])
        ->and($data['subsidiary']['name'])->toBe('PT Snapshot Lama')
        ->and($html)->toContain('data:image/png;base64,')
        ->and($html)->toContain('PT Snapshot Lama')
        ->and($html)->toContain('NPWP: 123456789012345')
        ->and($html)->toContain('Direktur: Direktur Snapshot')
        ->and($html)->toContain('Laptop')
        ->and($html)->toContain('SP/123/2026')
        ->and($html)->toContain('Kepala Sekolah Snapshot');

    foreach (['documents.surat-pesanan', 'documents.invoice'] as $view) {
        expect(view($view, $data)->render())
            ->toContain('data:image/png;base64,')
            ->toContain('PT Snapshot Lama');
    }

    expect(Pdf::loadView('documents.bast', $data)->output())
        ->toStartWith('%PDF');
});

test('document rendering fails clearly if the snapshot letterhead is missing', function () {
    $document = new ProcurementDocument([
        'document_type' => 'bast',
        'document_snapshot' => [
            'subsidiary' => [
                'name' => 'PT Tanpa Kop',
                'npwp' => '123456789012345',
                'director_name' => 'Direktur',
            ],
        ],
        'subsidiary_snapshot' => [
            'name' => 'PT Tanpa Kop',
            'npwp' => '123456789012345',
            'director_name' => 'Direktur',
        ],
    ]);

    expect(fn () => app(DocumentGenerationService::class)->viewData($document))
        ->toThrow(LogicException::class, 'Berkas kop surat tidak tersedia pada snapshot Anak Perusahaan');
});

test('official document issuance is blocked when the assigned snapshot has no letterhead', function () {
    $subsidiary = Subsidiary::factory()->create();
    $actor = User::factory()->admin()->create();
    $request = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    $request->assignSubsidiary($subsidiary, $actor);
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'quantity' => 1,
        'official_price' => 1000,
    ]);
    $request->lockTotals();
    $request->update(['status' => ProcurementRequest::STATUS_PRICE_AGREED]);

    expect(fn () => app(DocumentGenerationService::class)
        ->issue($request, 'purchase_order', $actor))
        ->toThrow(LogicException::class, 'Berkas kop surat tidak tersedia pada snapshot Anak Perusahaan')
        ->and($request->documents()->exists())->toBeFalse();
});
