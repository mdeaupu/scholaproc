<?php

namespace App\Livewire\Procurement;

use App\Models\ProcurementDocument;
use App\Models\ProcurementRequest;
use App\Models\User;
use Livewire\Component;

class DocumentPanel extends Component
{
    public ProcurementRequest $procurementRequest;

    private const EXPECTED_DOCUMENTS = [
        [
            'type' => 'planning',
            'name' => 'Dok. Perencanaan',
            'description' => 'Dokumen rencana kebutuhan barang/jasa dan anggaran',
        ],
        [
            'type' => 'negotiation',
            'name' => 'Dok. Hasil Nego',
            'description' => 'Rincian harga penawaran dan hasil negosiasi',
        ],
        [
            'type' => 'purchase_order',
            'name' => 'Surat Pesanan',
            'description' => 'Surat pesanan yang dibuat oleh Anak Perusahaan',
        ],
        [
            'type' => 'inspection',
            'name' => 'Surat Hasil Pemeriksaan',
            'description' => 'Dokumen hasil pemeriksaan barang/jasa yang diterima',
        ],
        [
            'type' => 'bast',
            'name' => 'Berita Acara Serah Terima',
            'description' => 'Bukti serah terima barang/jasa kepada sekolah',
        ],
        [
            'type' => 'invoice',
            'name' => 'Faktur',
            'description' => 'Faktur tagihan dari Anak Perusahaan',
        ],
        [
            'type' => 'receipt',
            'name' => 'Kuitansi',
            'description' => 'Bukti pembayaran yang diterbitkan',
        ],
        [
            'type' => 'cover',
            'name' => 'Cover',
            'description' => 'Sampul dokumen paket pengadaan',
        ],
    ];

    public function mount(ProcurementRequest $procurementRequest): void
    {
        $this->authorizeRequest($procurementRequest);
        $this->procurementRequest = $procurementRequest;
    }

    public function render()
    {
        $this->authorizeRequest($this->procurementRequest);
        $this->procurementRequest->load('documents');

        $documents = collect(self::EXPECTED_DOCUMENTS)
            ->map(function (array $expected): array {
                $document = $this->procurementRequest->documents
                    ->where('document_type', $expected['type'])
                    ->whereNotNull('issued_at')
                    ->sortByDesc('version')
                    ->first();

                return [
                    ...$expected,
                    'document' => $document,
                    'available' => $document instanceof ProcurementDocument,
                ];
            });

        return view('livewire.procurement.document-panel', [
            'documents' => $documents,
        ]);
    }

    private function authorizeRequest(ProcurementRequest $request): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);
        abort_unless(
            $user->isAdmin() || $user->isSuperAdmin() || ($user->isSchool() && $user->school_id === $request->school_id),
            404
        );
    }
}
