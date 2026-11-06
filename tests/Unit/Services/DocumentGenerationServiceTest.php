<?php

use App\Models\ProcurementDocument;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Models\Subsidiary;
use App\Models\User;
use App\Services\DocumentGenerationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
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
        ->and($service->viewFor(new ProcurementDocument(['document_type' => 'cover'])))
        ->toBe('documents.cover')
        ->and($service->viewFor(new ProcurementDocument(['document_type' => 'summary'])))
        ->toBe('documents.generated-summary')
        ->and($service->viewFor(new ProcurementDocument(['document_type' => 'receipt'])))
        ->toBe('documents.generated-receipt')
        ->and($service->viewFor(new ProcurementDocument(['document_type' => 'delivery'])))
        ->toBe('documents.delivery')
        ->and($service->viewFor(new ProcurementDocument(['document_type' => 'planning'])))
        ->toBe('documents.planning')
        ->and($service->viewFor(new ProcurementDocument(['document_type' => 'negotiation'])))
        ->toBe('documents.negotiation')
        ->and($service->viewFor(new ProcurementDocument(['document_type' => 'inspection'])))
        ->toBe('documents.inspection')
        ->and($service->viewFor(new ProcurementDocument(['document_type' => 'supplier_declaration'])))
        ->toBe('documents.supplier-declaration');
});

test('summary document can be issued after verification using the service number generator', function () {
    Storage::fake('public');
    $letterheadPath = UploadedFile::fake()->image('kop.png')->store('subsidiary-letterheads', 'public');
    $request = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    $actor = User::factory()->admin()->create();
    $subsidiary = Subsidiary::factory()->create();
    $request->assignSubsidiary($subsidiary, $actor);
    $subsidiary->update(['letterhead_path' => $letterheadPath]);

    $document = app(DocumentGenerationService::class)->issue($request, 'summary', $actor);

    expect($document->document_type)->toBe('summary')
        ->and($document->document_number)->toStartWith('SUMMARY/'.$request->id.'/')
        ->and($document->issued_at)->not->toBeNull();
});

test('negotiation document cannot be issued before price agreement', function () {
    $actor = User::factory()->admin()->create();
    $request = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    $request->assignSubsidiary(Subsidiary::factory()->create(), $actor);
    $request->update(['status' => ProcurementRequest::STATUS_NEGOTIATION]);

    expect(fn () => app(DocumentGenerationService::class)->issue($request, 'negotiation', $actor))
        ->toThrow(LogicException::class, 'belum memenuhi tahap penerbitan');
});

test('bast view receives the actual letterhead and identity from the issued snapshot', function () {
    Storage::fake('public');
    $path = UploadedFile::fake()->image('kop.png')->store('subsidiary-letterheads', 'public');
    $letterheadContents = Storage::disk('public')->get($path);
    $identity = [
        'name' => 'PT Snapshot Lama',
        'npwp' => '123456789012345',
        'director_name' => 'Direktur Snapshot',
        'address' => 'Alamat snapshot',
        'letterhead_path' => $path,
        'letterhead_url' => Storage::disk('public')->url($path),
        'letterhead_base64' => base64_encode($letterheadContents),
        'letterhead_mime_type' => 'image/png',
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

    Storage::disk('public')->delete($path);
    $service = app(DocumentGenerationService::class);
    $data = $service->viewData($document);
    $html = view($service->viewFor($document), $data)->render();

    expect(array_keys($data))->toBe(['snapshot'])
        ->and(data_get($data, 'snapshot.subsidiary.name'))->toBe('PT Snapshot Lama')
        ->and(data_get($data, 'snapshot.subsidiary.letterhead_base64'))->toBe($identity['letterhead_base64'])
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

    foreach (['documents.generated-summary', 'documents.generated-receipt'] as $view) {
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
        ->toThrow(LogicException::class, 'Snapshot dokumen tidak memiliki kop surat Base64 yang valid');
});

test('legacy document uses letterhead base64 from its frozen subsidiary snapshot', function () {
    $letterhead = base64_encode('frozen-letterhead-image');
    $document = new ProcurementDocument([
        'document_type' => 'bast',
        'document_snapshot' => [
            'subsidiary' => ['name' => 'PT Snapshot Lama'],
        ],
        'subsidiary_snapshot' => [
            'name' => 'PT Snapshot Lama',
            'npwp' => '123456789012345',
            'director_name' => 'Direktur Snapshot Lama',
            'letterhead_base64' => $letterhead,
            'letterhead_mime_type' => 'image/png',
        ],
    ]);

    $viewData = app(DocumentGenerationService::class)->viewData($document);

    expect(data_get($viewData, 'snapshot.subsidiary.letterhead_base64'))->toBe($letterhead)
        ->and(data_get($viewData, 'snapshot.subsidiary.director_name'))->toBe('Direktur Snapshot Lama');
});

test('legacy document embeds its stored letterhead file when base64 was not captured', function () {
    Storage::fake('public');
    $path = UploadedFile::fake()->image('legacy-kop.png')->store('subsidiary-letterheads', 'public');
    $contents = Storage::disk('public')->get($path);
    $document = new ProcurementDocument([
        'document_type' => 'bast',
        'document_snapshot' => [
            'subsidiary' => [
                'name' => 'PT Snapshot Lama',
                'letterhead_path' => $path,
            ],
        ],
    ]);

    $viewData = app(DocumentGenerationService::class)->viewData($document);

    expect(data_get($viewData, 'snapshot.subsidiary.letterhead_base64'))->toBe(base64_encode($contents))
        ->and(data_get($viewData, 'snapshot.subsidiary.letterhead_mime_type'))->toBe('image/png');
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

test('issued document embeds the letterhead and renders it after the storage file changes', function () {
    Storage::fake('public');
    $path = UploadedFile::fake()->image('kop.png')->store('subsidiary-letterheads', 'public');
    $letterheadContents = Storage::disk('public')->get($path);
    $actor = User::factory()->admin()->create();
    $request = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    $request->assignSubsidiary(
        Subsidiary::factory()->create(['letterhead_path' => $path]),
        $actor
    );
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'quantity' => 1,
        'official_price' => 1000,
    ]);
    $request->lockTotals();
    $request->update(['status' => ProcurementRequest::STATUS_PRICE_AGREED]);

    $document = app(DocumentGenerationService::class)->issue($request, 'purchase_order', $actor);
    Storage::disk('public')->delete($path);

    expect($document->subsidiary_snapshot['letterhead_base64'])
        ->toBe(base64_encode($letterheadContents))
        ->and(data_get($document->document_snapshot, 'subsidiary.letterhead_base64'))
        ->toBe(base64_encode($letterheadContents))
        ->and(data_get(app(DocumentGenerationService::class)->viewData($document), 'snapshot.subsidiary.letterhead_base64'))
        ->toBe(base64_encode($letterheadContents))
        ->and(data_get($document->document_snapshot, 'document_number'))
        ->toBe($document->document_number)
        ->and(data_get($document->document_snapshot, 'document_date'))
        ->toBe($document->document_date->toDateString())
        ->and(data_get($document->document_snapshot, 'document_date_numeric'))
        ->toBe($document->document_date->format('d-m-Y'))
        ->and(data_get($document->document_snapshot, 'document_date_words'))
        ->not->toBeEmpty();
});

test('download filenames use stable English document type names', function () {
    $service = app(DocumentGenerationService::class);
    $expectedFilenames = [
        'cover' => 'cover_42.pdf',
        'summary' => 'application_summary_42.pdf',
        'planning' => 'planning_document_42.pdf',
        'negotiation' => 'negotiation_document_42.pdf',
        'purchase_order' => 'purchase_order_42.pdf',
        'delivery' => 'delivery_document_42.pdf',
        'inspection' => 'inspection_report_42.pdf',
        'bast' => 'bast_42.pdf',
        'invoice' => 'invoice_42.pdf',
        'receipt' => 'receipt_42.pdf',
        'supplier_declaration' => 'supplier_declaration_42.pdf',
    ];

    foreach ($expectedFilenames as $type => $filename) {
        $document = new ProcurementDocument([
            'document_type' => $type,
        ]);
        $document->id = 42;

        expect($service->downloadFilename($document))->toBe($filename);
    }
});

test('document number allocation skips an existing global number collision', function () {
    Storage::fake('public');
    $path = UploadedFile::fake()->image('kop.png')->store('subsidiary-letterheads', 'public');
    $actor = User::factory()->admin()->create();
    $request = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    $request->assignSubsidiary(
        Subsidiary::factory()->create(['letterhead_path' => $path]),
        $actor
    );
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'quantity' => 1,
        'official_price' => 1000,
    ]);
    $request->lockTotals();
    $request->update(['status' => ProcurementRequest::STATUS_PRICE_AGREED]);

    $numberUsedByAnotherRequest = 'SP/'.$request->id.'/'.now()->format('Y');
    ProcurementDocument::factory()->create([
        'document_number' => $numberUsedByAnotherRequest,
    ]);

    $document = app(DocumentGenerationService::class)->issue($request, 'purchase_order', $actor);

    expect($document->document_number)->toBe($numberUsedByAnotherRequest.'-V2')
        ->and($document->version)->toBe(2)
        ->and(data_get($document->document_snapshot, 'version'))->toBe(2);
});

test('invoice and receipt issuance enforce their lifecycle prerequisites', function () {
    $actor = User::factory()->admin()->create();
    $invoiceRequest = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
        'tax_configuration_validated' => true,
    ]);
    $invoiceRequest->assignSubsidiary(Subsidiary::factory()->create(), $actor);
    $invoiceRequest->update(['status' => ProcurementRequest::STATUS_INVOICED]);
    $invoiceRequest->invoice()->create([
        'invoice_number' => 'INV-TEST-1',
        'invoice_date' => now()->toDateString(),
        'subtotal' => 1000,
        'tax_amount' => 0,
        'total_amount' => 1000,
        'tax_snapshot' => [],
        'price_snapshot' => [],
        'subsidiary_snapshot' => $invoiceRequest->subsidiary_snapshot,
        'processed_by' => $actor->id,
        'processed_at' => now(),
    ]);
    $invoiceRequest->receipts()->create([
        'received_at' => now()->toDateString(),
        'recorded_by' => $actor->id,
        'verified_by' => $actor->id,
        'verified_at' => now(),
    ]);

    expect(fn () => app(DocumentGenerationService::class)
        ->issue($invoiceRequest, 'invoice', $actor))
        ->toThrow(LogicException::class, 'belum memenuhi tahap penerbitan');

    $receiptRequest = ProcurementRequest::factory()->create([
        'status' => ProcurementRequest::STATUS_VERIFIED,
    ]);
    $receiptRequest->assignSubsidiary(Subsidiary::factory()->create(), $actor);
    $receiptRequest->update(['status' => ProcurementRequest::STATUS_PAYMENT_PENDING]);
    $invoice = $receiptRequest->invoice()->create([
        'invoice_number' => 'INV-TEST-2',
        'invoice_date' => now()->toDateString(),
        'subtotal' => 1000,
        'tax_amount' => 0,
        'total_amount' => 1000,
        'tax_snapshot' => [],
        'price_snapshot' => [],
        'subsidiary_snapshot' => $receiptRequest->subsidiary_snapshot,
        'processed_by' => $actor->id,
        'processed_at' => now(),
    ]);
    $invoice->payment()->create([
        'amount' => 1000,
        'method' => 'transfer',
        'paid_at' => now()->toDateString(),
    ]);

    expect(fn () => app(DocumentGenerationService::class)
        ->issue($receiptRequest, 'receipt', $actor))
        ->toThrow(LogicException::class, 'belum memenuhi tahap penerbitan');
});

test('procurement document numbers are unique across the system', function () {
    $firstRequest = ProcurementRequest::factory()->create();
    $secondRequest = ProcurementRequest::factory()->create();
    ProcurementDocument::factory()->create([
        'procurement_request_id' => $firstRequest->id,
        'document_number' => 'GLOBAL/2026/1',
    ]);

    expect(fn () => ProcurementDocument::factory()->create([
        'procurement_request_id' => $secondRequest->id,
        'document_number' => 'GLOBAL/2026/1',
    ]))->toThrow(QueryException::class);
});

test('issued document snapshot contains complete party, tax, work, item and payment data', function () {
    Storage::fake('public');
    $letterheadPath = UploadedFile::fake()->image('kop.png')->store('subsidiary-letterheads', 'public');
    $admin = User::factory()->admin()->create();
    $school = School::factory()->create([
        'address' => 'Alamat sekolah',
        'postal_code' => '43210',
        'phone_number' => '0263000000',
        'email' => 'sekolah@example.test',
        'principal_name' => 'Kepala Sekolah',
        'principal_nip' => '123456789',
    ]);
    $subsidiary = Subsidiary::factory()->create([
        'letterhead_path' => $letterheadPath,
        'nib' => 'NIB-123',
        'director_nik' => '1234567890123456',
        'director_npwp' => 'NPWP-DIR',
        'director_phone' => '0812000000',
        'director_address' => 'Alamat direktur',
        'commissioner_name' => 'Komisaris',
        'commissioner_nik' => '6543210987654321',
        'commissioner_address' => 'Alamat komisaris',
    ]);
    $deed = $subsidiary->legalDocuments()->create([
        'document_type' => 'deed',
        'document_number' => 'AKTA-1',
        'document_date' => '2025-01-02',
        'notary_name' => 'Notaris Contoh',
        'issuer' => 'Kemenkumham',
    ]);
    $subsidiary->legalDocuments()->create([
        'document_type' => 'business_permit',
        'document_number' => 'NIB-123',
        'issuer' => 'Kementerian Investasi',
    ]);
    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_VERIFIED,
        'start_date' => '2026-11-05',
        'end_date' => '2026-11-10',
        'work_duration_text' => '6 hari',
        'tax_configuration_validated' => true,
        'tax_configuration_basis' => 'Test basis',
        'tax_configuration_validated_by' => $admin->id,
        'tax_configuration_validated_at' => now(),
        'is_taxable' => true,
        'ppn_rate' => 11,
        'pph_22_rate' => 1.5,
        'pph_23_rate' => 2,
    ]);
    $request->assignSubsidiary($subsidiary, $admin);
    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'quantity' => 2,
        'estimated_price' => 1200,
        'official_price' => 1000,
    ]);
    $request->signatories()->createMany([
        ['role' => 'headmaster', 'name' => 'Kepala Snapshot', 'nip' => '111', 'title' => 'Kepala Sekolah'],
        ['role' => 'executor', 'name' => 'Pelaksana Snapshot', 'nip' => '444', 'title' => 'Pelaksana'],
        ['role' => 'inspector', 'name' => 'Pemeriksa Snapshot', 'nip' => '222', 'title' => 'Pemeriksa Barang'],
        ['role' => 'treasurer', 'name' => 'Bendahara Snapshot', 'nip' => '333', 'title' => 'Bendahara'],
    ]);
    $request->lockTotals();
    $request->update(['status' => ProcurementRequest::STATUS_PRICE_AGREED]);

    $document = app(DocumentGenerationService::class)->issue($request, 'purchase_order', $admin);
    $snapshot = $document->document_snapshot;

    expect($document->subsidiary_snapshot['nib'])->toBe('NIB-123')
        ->and($document->subsidiary_snapshot['director_nik'])->toBe('1234567890123456')
        ->and($document->subsidiary_snapshot['deeds'][0]['id'])->toBe($deed->id)
        ->and(data_get($snapshot, 'school.phone'))->toBe('0263000000')
        ->and(data_get($snapshot, 'school.officials.headmaster.name'))->toBe('Kepala Snapshot')
        ->and(data_get($snapshot, 'school.officials.executor.name'))->toBe('Pelaksana Snapshot')
        ->and(data_get($snapshot, 'school.officials.inspector.title'))->toBe('Pemeriksa Barang')
        ->and(data_get($snapshot, 'transaction.start_date'))->toBe('2026-11-05')
        ->and(data_get($snapshot, 'transaction.end_date'))->toBe('2026-11-10')
        ->and(data_get($snapshot, 'transaction.duration'))->toBe('6 hari')
        ->and(data_get($snapshot, 'transaction.items.0.offered_price'))->toEqual(1200)
        ->and(data_get($snapshot, 'transaction.items.0.negotiated_price'))->toEqual(1000)
        ->and(data_get($snapshot, 'transaction.tax_breakdown.ppn.rate'))->toEqual(11)
        ->and(data_get($snapshot, 'transaction.tax_breakdown.pph_22.rate'))->toEqual(1.5)
        ->and(data_get($snapshot, 'transaction.tax_breakdown.pph_23.rate'))->toEqual(2)
        ->and(data_get($snapshot, 'transaction.amount_in_words'))->toContain('Rupiah');
});

test('all mapped document templates render from snapshot-only view data', function () {
    $identity = [
        'name' => 'PT Snapshot',
        'npwp' => '123456789012345',
        'nib' => 'NIB-1',
        'director_name' => 'Direktur Snapshot',
        'director_nik' => '1234567890123456',
        'address' => 'Alamat penyedia',
        'letterhead_base64' => base64_encode('test-image'),
        'letterhead_mime_type' => 'image/png',
        'legal_documents' => [],
        'deeds' => [],
        'business_permits' => [],
    ];
    $snapshot = [
        'school' => [
            'name' => 'Sekolah Snapshot',
            'npsn' => '12345678',
            'address' => 'Alamat sekolah',
            'officials' => [
                'headmaster' => ['name' => 'Kepala Sekolah', 'nip' => '111'],
                'inspector' => ['name' => 'Pemeriksa', 'nip' => '222'],
                'treasurer' => ['name' => 'Bendahara', 'nip' => '333'],
            ],
        ],
        'subsidiary' => $identity,
        'transaction' => [
            'budget_year' => '2026',
            'package_category' => 'Paket Contoh',
            'funding_source' => 'BOSP',
            'start_date' => '2026-11-05',
            'end_date' => '2026-11-10',
            'target_receipt_date_numeric' => '10-11-2026',
            'target_receipt_date_words' => 'sepuluh november dua ribu dua puluh enam',
            'duration' => '6 hari',
            'status' => 'price_agreed',
            'subtotal' => 1000,
            'tax' => ['validated' => true],
            'tax_breakdown' => ['ppn' => ['rate' => 11, 'amount' => 110]],
            'grand_total' => 1110,
            'amount' => 1110,
            'amount_in_words' => 'Seribu Seratus Sepuluh Rupiah',
            'items' => [[
                'item_name' => 'Laptop',
                'item' => 'Laptop',
                'specification' => 'RAM 8 GB',
                'unit' => 'Unit',
                'quantity' => 1,
                'ordered_quantity' => 1,
                'offered_price' => 1200,
                'offered_amount' => 1200,
                'negotiated_price' => 1000,
                'negotiated_amount' => 1000,
                'estimated_price' => 1200,
                'official_price' => 1000,
                'amount' => 1000,
                'condition' => 'good',
                'issue_notes' => null,
            ]],
        ],
        'stage' => [
            'purchase_order' => ['number' => 'SP/1/2026', 'date' => '2026-11-05'],
            'invoice_number' => 'INV/1/2026',
            'invoice_date' => '2026-11-06',
            'subtotal' => 1000,
            'tax_amount' => 110,
            'total_amount' => 1110,
            'received_at' => '2026-11-07',
            'inspection_date_numeric' => '07-11-2026',
            'inspection_date_words' => 'tujuh november dua ribu dua puluh enam',
            'delivered_at' => '2026-11-06',
            'items' => [[
                'item' => 'Laptop',
                'specification' => 'RAM 8 GB',
                'quantity' => 1,
                'ordered_quantity' => 1,
                'condition' => 'good',
            ]],
        ],
        'documents' => ['receipt' => null],
    ];

    foreach ([
        'cover',
        'planning',
        'negotiation',
        'purchase_order',
        'delivery',
        'inspection',
        'bast',
        'invoice',
        'receipt',
        'supplier_declaration',
    ] as $type) {
        $document = new ProcurementDocument([
            'document_type' => $type,
            'document_number' => 'DOC/1/2026',
            'document_date' => '2026-11-08',
            'document_snapshot' => $snapshot,
            'subsidiary_snapshot' => $identity,
        ]);
        $service = app(DocumentGenerationService::class);
        $html = view($service->viewFor($document), $service->viewData($document))->render();

        expect(trim(strip_tags($html)))->not->toBeEmpty();

        if ($type === 'cover') {
            expect($html)->toContain('<p>-</p>');
        } elseif ($type === 'negotiation') {
            expect($html)->toContain('10-11-2026')
                ->and($html)->toContain('sepuluh november dua ribu dua puluh enam');
        } elseif ($type === 'inspection') {
            expect($html)->toContain('07-11-2026')
                ->and($html)->toContain('tujuh november dua ribu dua puluh enam');
        }
    }
});
