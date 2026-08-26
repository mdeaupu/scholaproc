<?php

namespace App\Livewire\Procurement;

use App\Models\ProcurementRequest;
use App\Models\ProcurementVerificationFile;
use Exception;
use Illuminate\Support\Facades\Storage;
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

    public bool $showConfirmModal = false;

    public bool $showDeleteModal = false;

    public ?int $deletingFileId = null;

    public $photos = [];

    public $documents = [];

    public function mount(ProcurementRequest $procurementRequest): void
    {
        $this->procurementRequest = $procurementRequest;
        $this->loadFiles();
    }

    public function loadFiles(): void
    {
        $this->procurementRequest->load('verificationFiles.uploader');
        $allFiles = $this->procurementRequest->verificationFiles;

        $this->photos = $allFiles->where('file_type', ProcurementVerificationFile::TYPE_PHOTO)->values();
        $this->documents = $allFiles->where('file_type', ProcurementVerificationFile::TYPE_SIGNED_DOCUMENT)->values();
    }

    public function uploadPhoto(): void
    {
        $this->authorizeSchool();

        if ($this->procurementRequest->verified_at !== null) {
            $this->error('Tidak dapat mengunggah file setelah verifikasi dikonfirmasi.');

            return;
        }

        $this->validate([
            'photoFiles.*' => 'file|image|max:5120|mimes:jpg,jpeg,png',
        ], [
            'photoFiles.*.file' => 'File foto tidak valid.',
            'photoFiles.*.image' => 'File harus berupa gambar.',
            'photoFiles.*.max' => 'Ukuran foto maksimal 5MB.',
            'photoFiles.*.mimes' => 'Format foto harus JPG atau PNG.',
        ]);

        try {
            foreach ($this->photoFiles as $photo) {
                $filename = time().'_'.uniqid().'.'.$photo->getClientOriginalExtension();
                $path = $photo->storeAs(
                    'verification_files/'.$this->procurementRequest->id,
                    $filename,
                    'public'
                );

                ProcurementVerificationFile::create([
                    'procurement_request_id' => $this->procurementRequest->id,
                    'file_type' => ProcurementVerificationFile::TYPE_PHOTO,
                    'file_path' => $path,
                    'uploaded_by' => auth()->id(),
                    'notes' => $this->photoNotes ?: null,
                    'uploaded_at' => now(),
                ]);
            }

            $this->photoFiles = [];
            $this->photoNotes = '';
            $this->loadFiles();
            $this->success('Foto barang berhasil diunggah.');
        } catch (Exception $e) {
            $this->error('Gagal mengunggah foto: '.$e->getMessage());
        }
    }

    public function uploadDocument(): void
    {
        $this->authorizeSchool();

        if ($this->procurementRequest->verified_at !== null) {
            $this->error('Tidak dapat mengunggah file setelah verifikasi dikonfirmasi.');

            return;
        }

        $this->validate([
            'documentFiles.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png',
        ], [
            'documentFiles.*.file' => 'File dokumen tidak valid.',
            'documentFiles.*.max' => 'Ukuran dokumen maksimal 10MB.',
            'documentFiles.*.mimes' => 'Format dokumen harus PDF, JPG, atau PNG.',
        ]);

        try {
            foreach ($this->documentFiles as $doc) {
                $filename = time().'_'.uniqid().'.'.$doc->getClientOriginalExtension();
                $path = $doc->storeAs(
                    'verification_files/'.$this->procurementRequest->id,
                    $filename,
                    'public'
                );

                ProcurementVerificationFile::create([
                    'procurement_request_id' => $this->procurementRequest->id,
                    'file_type' => ProcurementVerificationFile::TYPE_SIGNED_DOCUMENT,
                    'file_path' => $path,
                    'uploaded_by' => auth()->id(),
                    'notes' => $this->documentNotes ?: null,
                    'uploaded_at' => now(),
                ]);
            }

            $this->documentFiles = [];
            $this->documentNotes = '';
            $this->loadFiles();
            $this->success('Dokumen bertanda tangan berhasil diunggah.');
        } catch (Exception $e) {
            $this->error('Gagal mengunggah dokumen: '.$e->getMessage());
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

    public function confirmDelete(): void
    {
        $this->authorizeSchool();

        try {
            $file = ProcurementVerificationFile::findOrFail($this->deletingFileId);

            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }

            $file->delete();
            $this->loadFiles();
            $this->showDeleteModal = false;
            $this->deletingFileId = null;
            $this->success('File berhasil dihapus.');
        } catch (Exception $e) {
            $this->error('Gagal menghapus file: '.$e->getMessage());
        }
    }

    public function openConfirmModal(): void
    {
        $this->authorizeSchool();

        if (! $this->procurementRequest->canMarkVerified()) {
            $this->error('Verifikasi tidak dapat dilakukan. Pastikan minimal 1 dokumen bertanda tangan sudah diunggah.');

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
