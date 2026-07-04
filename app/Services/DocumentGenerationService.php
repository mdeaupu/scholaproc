<?php

namespace App\Services;

use App\Models\GeneratedDocument;
use App\Models\ProcurementDocument;
use App\Models\ProcurementRequest;
use App\Models\ProcurementSignatory;
use Exception;

class DocumentGenerationService
{
    private const VIEWS = [
        'cover' => 'documents.cover',
        'planning' => 'documents.planning',
        'negotiation' => 'documents.negotiation',
        'purchase_order' => 'documents.purchase-order',
        'inspection' => 'documents.inspection',
        'bast' => 'documents.bast',
        'invoice' => 'documents.invoice',
        'receipt' => 'documents.receipt',
    ];

    private function prepareCommonData(ProcurementRequest $procurement): array
    {
        if (!$procurement->isReadyForDocumentGeneration()) {
            throw new Exception(
                'Pengajuan belum siap untuk generate dokumen. '
                . 'Pastikan supplier, penandatangan (headmaster, inspector, treasurer), '
                . 'dan harga resmi setiap item sudah lengkap.'
            );
        }

        $procurement->loadMissing([
            'school.setting',
            'supplier',
            'signatories',
            'items',
            'documents',
        ]);

        if ($procurement->documents->count() < count(self::VIEWS)) {
            $procurement->generateOfficialDocuments();
            $procurement->load('documents');
        }

        $signatories = $procurement->signatories->keyBy('role');

        $subtotal = $procurement->officialSubtotal() > 0
            ? $procurement->officialSubtotal()
            : $procurement->estimatedSubtotal();

        $ppn = $procurement->totalPpn();
        $pph22 = $procurement->totalPph22();
        $pph23 = $procurement->totalPph23();
        $totalAfterTax = $procurement->netTotal();

        return [
            'procurement' => $procurement,
            'school' => $procurement->school,
            'schoolSetting' => $procurement->school?->setting,
            'supplier' => $procurement->supplier,
            'items' => $procurement->items,

            'headmaster' => $this->signatoryOrPlaceholder($signatories, 'headmaster'),
            'inspector' => $this->signatoryOrPlaceholder($signatories, 'inspector'),

            'subtotal' => $subtotal,
            'ppn' => $ppn,
            'pph22' => $pph22,
            'pph23' => $pph23,
            'totalAfterTax' => $totalAfterTax,
            'totalNegosiasi' => $totalAfterTax,
            'totalAnggaran' => $procurement->estimatedSubtotal(),
            'totalItems' => $procurement->items->count(),

            'document' => null,
            'planningDocument' => $this->documentOrFail($procurement, 'planning'),
            'purchaseOrderDocument' => $this->documentOrFail($procurement, 'purchase_order'),
            'receiptDocument' => $this->documentOrFail($procurement, 'receipt'),

            'cityName' => 'Cianjur',
            'placeDate' => 'Cianjur, ' . now()->translatedFormat('d F Y'),
        ];
    }

    private function documentOrFail(ProcurementRequest $procurement, string $type): ProcurementDocument
    {
        $document = $procurement->documents->firstWhere('document_type', $type);

        if (!$document) {
            throw new Exception(
                "Nomor dokumen untuk tipe '{$type}' belum tersedia. "
                . 'Jalankan $procurement->generateOfficialDocuments() terlebih dahulu.'
            );
        }

        return $document;
    }

    private function signatoryOrPlaceholder($signatories, string $role): object
    {
        /** @var ProcurementSignatory|null $signatory */
        $signatory = $signatories->get($role);

        return (object) [
            'name' => $signatory->name ?? '.......................',
            'nip' => $signatory->nip ?? '-',
            'title' => $signatory->title ?? null,
        ];
    }

    public function generateCover(ProcurementRequest $procurement): GeneratedDocument
    {
        $data = $this->prepareCommonData($procurement);
        $data['document'] = $this->documentOrFail($procurement, 'cover');

        return GeneratedDocument::generatePdf($procurement, 'cover', self::VIEWS['cover'], $data);
    }

    public function generatePlanning(ProcurementRequest $procurement): GeneratedDocument
    {
        $data = $this->prepareCommonData($procurement);
        $data['document'] = $data['planningDocument'];

        return GeneratedDocument::generatePdf($procurement, 'planning', self::VIEWS['planning'], $data);
    }

    public function generateNegotiation(ProcurementRequest $procurement): GeneratedDocument
    {
        $data = $this->prepareCommonData($procurement);
        $data['document'] = $this->documentOrFail($procurement, 'negotiation');

        return GeneratedDocument::generatePdf($procurement, 'negotiation', self::VIEWS['negotiation'], $data);
    }

    public function generatePurchaseOrder(ProcurementRequest $procurement): GeneratedDocument
    {
        $data = $this->prepareCommonData($procurement);
        $data['document'] = $data['purchaseOrderDocument'];

        return GeneratedDocument::generatePdf($procurement, 'purchase_order', self::VIEWS['purchase_order'], $data);
    }

    public function generateInspection(ProcurementRequest $procurement): GeneratedDocument
    {
        $data = $this->prepareCommonData($procurement);
        $data['document'] = $this->documentOrFail($procurement, 'inspection');

        return GeneratedDocument::generatePdf($procurement, 'inspection', self::VIEWS['inspection'], $data);
    }

    public function generateBast(ProcurementRequest $procurement): GeneratedDocument
    {
        $data = $this->prepareCommonData($procurement);
        $data['document'] = $this->documentOrFail($procurement, 'bast');

        return GeneratedDocument::generatePdf($procurement, 'bast', self::VIEWS['bast'], $data);
    }

    public function generateInvoice(ProcurementRequest $procurement): GeneratedDocument
    {
        $data = $this->prepareCommonData($procurement);
        $data['document'] = $this->documentOrFail($procurement, 'invoice');

        return GeneratedDocument::generatePdf($procurement, 'invoice', self::VIEWS['invoice'], $data);
    }

    public function generateReceipt(ProcurementRequest $procurement): GeneratedDocument
    {
        $data = $this->prepareCommonData($procurement);
        $data['document'] = $data['receiptDocument'];

        return GeneratedDocument::generatePdf($procurement, 'receipt', self::VIEWS['receipt'], $data);
    }
}