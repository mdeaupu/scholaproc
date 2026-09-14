<?php

use App\Models\ProcurementNegotiation;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Models\Supplier;
use App\Services\NotificationContentBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createBuilderProcurementWithRelations(array $overrides = []): ProcurementRequest
{
    $school = School::factory()->create(['name' => 'SMP Negeri 1 Jakarta']);
    $supplier = Supplier::factory()->create(['company_name' => 'PT Supplier Jaya']);

    $pr = ProcurementRequest::factory()->create(array_merge([
        'school_id' => $school->id,
        'supplier_id' => $supplier->id,
    ], $overrides));

    return $pr;
}

test('buildSubmitted contains school name and category', function () {
    $pr = createBuilderProcurementWithRelations();

    ProcurementRequestItem::factory()->count(3)->create([
        'procurement_request_id' => $pr->id,
    ]);

    $message = NotificationContentBuilder::buildSubmitted($pr);

    expect($message)->toContain('SMP Negeri 1 Jakarta');
    expect($message)->toContain('Total item: 3');
    expect($message)->toContain('Pengajuan pengadaan baru telah diajukan');
});

test('buildVerified contains verification info', function () {
    $pr = createBuilderProcurementWithRelations();

    $message = NotificationContentBuilder::buildVerified($pr);

    expect($message)->toContain('SMP Negeri 1 Jakarta');
    expect($message)->toContain('telah diverifikasi oleh Admin');
    expect($message)->toContain('Silakan tentukan supplier');
});

test('buildRejected contains rejection reason', function () {
    $pr = createBuilderProcurementWithRelations();

    $message = NotificationContentBuilder::buildRejected($pr, 'Dokumen tidak lengkap');

    expect($message)->toContain('DITOLAK');
    expect($message)->toContain('Alasan: Dokumen tidak lengkap');
    expect($message)->toContain('Silakan perbaiki pengajuan');
});

test('buildSupplierAssigned contains supplier info', function () {
    $pr = createBuilderProcurementWithRelations();

    $message = NotificationContentBuilder::buildSupplierAssigned($pr);

    expect($message)->toContain('SMP Negeri 1 Jakarta');
    expect($message)->toContain('PT Supplier Jaya');
    expect($message)->toContain('Supplier telah ditunjuk');
    expect($message)->toContain('Negosiasi harga dapat dimulai');
});

test('buildNegotiationOffer contains item and price info', function () {
    $pr = createBuilderProcurementWithRelations();
    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $pr->id,
        'item_name' => 'Meja Guru',
        'official_price' => 500000,
    ]);
    $negotiation = ProcurementNegotiation::factory()->create([
        'procurement_request_item_id' => $item->id,
        'offered_price' => 450000,
        'round_number' => 1,
        'notes' => 'Harga promo',
    ]);

    $message = NotificationContentBuilder::buildNegotiationOffer($pr, $item, $negotiation);

    expect($message)->toContain('Meja Guru');
    expect($message)->toContain('500.000');
    expect($message)->toContain('Putaran: 1');
    expect($message)->toContain('Catatan: Harga promo');
});

test('buildNegotiationDecided accepted shows accepted status', function () {
    $pr = createBuilderProcurementWithRelations();
    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $pr->id,
        'item_name' => 'Kursi Siswa',
    ]);
    $negotiation = ProcurementNegotiation::factory()->create([
        'procurement_request_item_id' => $item->id,
        'offered_price' => 300000,
    ]);

    $message = NotificationContentBuilder::buildNegotiationDecided($pr, $item, $negotiation, 'accepted');

    expect($message)->toContain('Kursi Siswa');
    expect($message)->toContain('300.000');
    expect($message)->toContain('DITERIMA');
    expect($message)->toContain('Harga resmi untuk item ini telah ditetapkan');
});

test('buildNegotiationDecided rejected shows rejected status with reason', function () {
    $pr = createBuilderProcurementWithRelations();
    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $pr->id,
        'item_name' => 'Papan Tulis',
    ]);
    $negotiation = ProcurementNegotiation::factory()->create([
        'procurement_request_item_id' => $item->id,
        'offered_price' => 200000,
        'notes' => 'Harga terlalu tinggi',
    ]);

    $message = NotificationContentBuilder::buildNegotiationDecided($pr, $item, $negotiation, 'rejected');

    expect($message)->toContain('Papan Tulis');
    expect($message)->toContain('200.000');
    expect($message)->toContain('DITOLAK');
    expect($message)->toContain('Alasan: Harga terlalu tinggi');
});

test('buildItemsPrepared contains item count', function () {
    $pr = createBuilderProcurementWithRelations();

    ProcurementRequestItem::factory()->count(5)->create([
        'procurement_request_id' => $pr->id,
    ]);

    $message = NotificationContentBuilder::buildItemsPrepared($pr);

    expect($message)->toContain('SMP Negeri 1 Jakarta');
    expect($message)->toContain('PT Supplier Jaya');
    expect($message)->toContain('Total item: 5');
    expect($message)->toContain('Barang siap untuk dikirim');
});

test('buildReceiptVerified contains verification details', function () {
    $pr = createBuilderProcurementWithRelations([
        'verified_at' => now(),
    ]);

    $message = NotificationContentBuilder::buildReceiptVerified($pr);

    expect($message)->toContain('SMP Negeri 1 Jakarta');
    expect($message)->toContain('Barang pengadaan telah diverifikasi diterima');
    expect($message)->toContain('WIB');
});

test('buildCompleted contains grand total', function () {
    $pr = createBuilderProcurementWithRelations([
        'grand_total' => 5000000,
    ]);

    $message = NotificationContentBuilder::buildCompleted($pr);

    expect($message)->toContain('SMP Negeri 1 Jakarta');
    expect($message)->toContain('PT Supplier Jaya');
    expect($message)->toContain('5.000.000');
    expect($message)->toContain('Pengadaan telah SELESAI');
    expect($message)->toContain('Dokumen pengadaan sudah tersedia untuk diunduh');
});

test('buildDocumentGenerated contains school name and document count', function () {
    $pr = createBuilderProcurementWithRelations();

    $message = NotificationContentBuilder::buildDocumentGenerated($pr);

    expect($message)->toContain('SMP Negeri 1 Jakarta');
    expect($message)->toContain('Dokumen pengadaan telah tersedia');
    expect($message)->toContain('Silakan unduh melalui sistem pengadaan');
});
