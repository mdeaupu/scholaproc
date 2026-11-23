<?php

namespace App\Livewire\Procurement;

use App\Models\ProcurementRequest;
use App\Models\ProcurementVerificationFile;
use App\Services\ProcurementProcessingService;
use Exception;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class VerificationPanel extends Component
{
    use Toast, WithFileUploads;

    public ProcurementRequest $procurementRequest;

    public $photoFiles = [];

    public $documentFiles = [];

    public string $photoNotes = '';

    public string $documentNotes = '';

    public string $receiptDate = '';

    public array $receiptRows = [];

    public string $receiptNotes = '';

    public bool $showConfirmModal = false;

    public bool $showDeleteModal = false;

    public ?int $deletingFileId = null;

    public $photos = [];

    public $documents = [];

    public function mount(ProcurementRequest $procurementRequest): void
    {
        $this->procurementRequest = $procurementRequest;
        $this->loadFiles();
        $this->resetReceiptForm();
    }

    public function loadFiles(): void
    {
        $this->procurementRequest->load([
            'school',
            'verifiedBy',
            'items.unit',
            'items.receiptItems.receipt',
            'items.deliveryItems',
            'deliveries.recordedBy',
            'receipts.recordedBy',
            'receipts.verifiedBy',
            'receipts.items',
            'verificationFiles.uploader',
        ]);
        $allFiles = $this->procurementRequest->verificationFiles;

        $this->photos = $allFiles->where('file_type', ProcurementVerificationFile::TYPE_PHOTO)->values();
        $this->documents = $allFiles->where('file_type', ProcurementVerificationFile::TYPE_SIGNED_DOCUMENT)->values();
    }

    public function recordReceipt(ProcurementProcessingService $service): void
    {
        $this->authorizeSchool();

        $this->validate([
            'receiptDate' => ['required', 'date'],
            'receiptRows' => ['required', 'array'],
            'receiptRows.*.quantity' => ['nullable', 'integer', 'min:0'],
            'receiptRows.*.condition' => ['required', 'in:good,incomplete,damaged,non_conforming'],
            'receiptRows.*.issue_notes' => ['nullable', 'string', 'max:2000'],
            'receiptNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $rows = array_filter(
            $this->receiptRows,
            fn (array $row): bool => (int) ($row['quantity'] ?? 0) > 0
        );

        try {
            $service->recordReceipt(
                $this->procurementRequest,
                auth()->user(),
                $this->receiptDate,
                $rows,
                $this->receiptNotes ?: null
            );

            $this->procurementRequest->refresh();
            $this->loadFiles();
            $this->resetReceiptForm();
            $this->success('Penerimaan barang berhasil dicatat.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    private function resetReceiptForm(): void
    {
        $this->receiptDate = now()->toDateString();
        $this->receiptNotes = '';
        $this->receiptRows = $this->procurementRequest->items
            ->mapWithKeys(fn ($item) => [
                $item->id => [
                    'quantity' => null,
                    'condition' => 'good',
                    'issue_notes' => null,
                ],
            ])
            ->all();
    }

    public function uploadPhoto(ProcurementProcessingService $service): void
    {
        $this->authorizeSchool();

        try {
            $service->storeVerificationFiles(
                $this->procurementRequest,
                auth()->user(),
                ProcurementVerificationFile::TYPE_PHOTO,
                $this->photoFiles,
                $this->photoNotes ?: null
            );

            $this->photoFiles = [];
            $this->photoNotes = '';
            $this->loadFiles();
            $this->success('Foto barang berhasil diunggah.');
        } catch (ValidationException $exception) {
            $this->error(collect($exception->errors())->flatten()->first() ?? 'File foto tidak valid.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function uploadDocument(ProcurementProcessingService $service): void
    {
        $this->authorizeSchool();

        try {
            $service->storeVerificationFiles(
                $this->procurementRequest,
                auth()->user(),
                ProcurementVerificationFile::TYPE_SIGNED_DOCUMENT,
                $this->documentFiles,
                $this->documentNotes ?: null
            );

            $this->documentFiles = [];
            $this->documentNotes = '';
            $this->loadFiles();
            $this->success('Dokumen bertanda tangan berhasil diunggah.');
        } catch (ValidationException $exception) {
            $this->error(collect($exception->errors())->flatten()->first() ?? 'File dokumen tidak valid.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function openDeleteModal(int $fileId): void
    {
        $this->authorizeSchool();

        if ($this->procurementRequest->verified_at !== null) {
            $this->error('Tidak dapat menghapus file setelah verifikasi dikonfirmasi.');

            return;
        }

        $this->deletingFileId = $fileId;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(ProcurementProcessingService $service): void
    {
        $this->authorizeSchool();

        if ($this->deletingFileId === null) {
            return;
        }

        try {
            $service->deleteVerificationFile($this->procurementRequest, auth()->user(), $this->deletingFileId);
            $this->loadFiles();
            $this->showDeleteModal = false;
            $this->deletingFileId = null;
            $this->success('File berhasil dihapus.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function openConfirmModal(): void
    {
        $this->authorizeSchool();

        if (! $this->procurementRequest->canConfirmReceipt()) {
            $this->error('Verifikasi tidak dapat dilakukan. Pastikan minimal 1 dokumen bertanda tangan sudah diunggah dan status pengadaan siap diverifikasi.');

            return;
        }

        $this->showConfirmModal = true;
    }

    public function confirmMarkVerified(): void
    {
        $this->authorizeSchool();

        try {
            $this->procurementRequest->markVerified(auth()->user());
            $this->procurementRequest->notifyReceiptVerified();
            $this->procurementRequest->refresh();
            $this->loadFiles();
            $this->showConfirmModal = false;
            $this->success('Barang telah berhasil diverifikasi diterima.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    private function authorizeSchool(): void
    {
        $user = auth()->user();
        abort_unless($user->isSchool() && $user->school_id === $this->procurementRequest->school_id, 403);
    }

    public function getFileUrl(string $filePath): string
    {
        return asset('storage/'.$filePath);
    }

    public function render()
    {
        return view('livewire.procurement.verification-panel', [
            'photos' => $this->photos,
            'documents' => $this->documents,
        ])->layout('layouts.app');
    }
}
