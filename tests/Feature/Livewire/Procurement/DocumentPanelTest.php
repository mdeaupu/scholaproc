<?php

use App\Livewire\Procurement\DocumentPanel;
use App\Livewire\Procurement\ProcurementRequestDetail;
use App\Models\ProcurementDocument;
use App\Models\ProcurementRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('document panel lists the eight expected documents and matches issued request documents', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $request = ProcurementRequest::factory()->create(['school_id' => $school->id]);
    $document = ProcurementDocument::factory()->create([
        'procurement_request_id' => $request->id,
        'document_type' => 'purchase_order',
        'document_number' => 'SP/1/2026',
    ]);
    DB::table('procurement_documents')
        ->where('id', $document->id)
        ->update(['issued_at' => now()]);

    Livewire::actingAs($user)
        ->test(DocumentPanel::class, ['procurementRequest' => $request])
        ->assertStatus(200)
        ->assertSee('Dokumen Terkait')
        ->assertSee('Dokumen akan muncul sesuai dengan status transaksi.')
        ->assertSee('Dok. Perencanaan')
        ->assertSee('Dok. Hasil Nego')
        ->assertSee('Surat Pesanan')
        ->assertSee('Surat Hasil Pemeriksaan')
        ->assertSee('Berita Acara Serah Terima')
        ->assertSee('Faktur')
        ->assertSee('Kuitansi')
        ->assertSee('Cover')
        ->assertSee(route('procurement.documents.download', [$request, $document]))
        ->assertSee('Tersedia')
        ->assertSee('Belum Tersedia');
});

test('document panel cross-matches all eight document types against issued records', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $request = ProcurementRequest::factory()->create(['school_id' => $school->id]);
    $types = [
        'planning',
        'negotiation',
        'purchase_order',
        'inspection',
        'bast',
        'invoice',
        'receipt',
        'cover',
    ];

    foreach ($types as $type) {
        $document = ProcurementDocument::factory()->create([
            'procurement_request_id' => $request->id,
            'document_type' => $type,
            'document_number' => strtoupper($type).'/1/2026',
        ]);
        DB::table('procurement_documents')
            ->where('id', $document->id)
            ->update(['issued_at' => now()]);
    }

    $component = Livewire::actingAs($user)
        ->test(DocumentPanel::class, ['procurementRequest' => $request])
        ->assertSee('Dok. Perencanaan')
        ->assertSee('Dok. Hasil Nego')
        ->assertSee('Surat Pesanan')
        ->assertSee('Surat Hasil Pemeriksaan')
        ->assertSee('Berita Acara Serah Terima')
        ->assertSee('Faktur')
        ->assertSee('Kuitansi')
        ->assertSee('Cover');

    expect($component->viewData('documents'))->toHaveCount(8)
        ->and($component->viewData('documents')->where('available', true))->toHaveCount(8);
});

test('request detail shows the document table even before documents are issued', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_NEGOTIATION,
    ]);

    Livewire::actingAs($user)
        ->test(ProcurementRequestDetail::class, ['procurementRequest' => $request])
        ->call('setActiveTab', 'dokumen')
        ->assertSee('Dokumen Terkait')
        ->assertSee('Dok. Perencanaan')
        ->assertSee('Dok. Hasil Nego')
        ->assertSee('Surat Pesanan')
        ->assertSee('Surat Hasil Pemeriksaan')
        ->assertSee('Berita Acara Serah Terima')
        ->assertSee('Faktur')
        ->assertSee('Kuitansi')
        ->assertSee('Cover')
        ->assertSee('Belum Tersedia');
});

test('school cannot view another school document panel', function () {
    $userSchool = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $userSchool->id]);
    $request = ProcurementRequest::factory()->create(['school_id' => $otherSchool->id]);

    Livewire::actingAs($user)
        ->test(DocumentPanel::class, ['procurementRequest' => $request])
        ->assertNotFound();
});
