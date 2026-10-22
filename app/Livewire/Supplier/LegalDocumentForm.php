<?php

namespace App\Livewire\Supplier;

use App\Enums\DocumentType;
use App\Models\Supplier;
use App\Models\SupplierLegalDocument;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Mary\Traits\Toast;

class LegalDocumentForm extends Component
{
    use Toast;

    public Supplier $supplier;

    public ?SupplierLegalDocument $document = null;

    public string $document_type = '';

    public string $document_number = '';

    public string $document_date = '';

    public ?string $notary_name = null;

    public ?string $issuer = null;

    public ?string $valid_until = null;

    public bool $noExpiry = false;

    public bool $isEdit = false;

    public function getDocumentTypesProperty(): array
    {
        $existingTypes = $this->supplier->legalDocuments
            ->pluck('document_type')
            ->map(fn ($type) => $type instanceof DocumentType ? $type->value : $type)
            ->toArray();

        if ($this->isEdit && $this->document) {
            $currentType = $this->document->document_type instanceof DocumentType
                ? $this->document->document_type->value
                : $this->document->document_type;

            $existingTypes = array_diff($existingTypes, [$currentType]);
        }

        $options = [];
        foreach (DocumentType::cases() as $case) {
            if (! in_array($case->value, $existingTypes)) {
                $options[] = ['id' => $case->value, 'name' => $case->label()];
            }
        }

        return $options;
    }

    public function getRequiredDocumentsProperty(): array
    {
        $existingTypes = $this->supplier->legalDocuments
            ->map(fn ($doc) => $doc->document_type instanceof DocumentType
                ? $doc->document_type->value
                : $doc->document_type)
            ->toArray();

        $descriptions = [
            DocumentType::DEED->value => 'Dokumen legalitas perusahaan',
            DocumentType::NPWP->value => 'Nomor Pokok Wajib Pajak',
            DocumentType::BUSINESS_PERMIT->value => 'Surat izin usaha (NIB/SIUP)',
            DocumentType::OTHER->value => 'Dokumen pendukung lainnya',
        ];

        $orderedTypes = [
            DocumentType::DEED,
            DocumentType::NPWP,
            DocumentType::BUSINESS_PERMIT,
            DocumentType::OTHER,
        ];

        return collect($orderedTypes)->map(fn (DocumentType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $descriptions[$type->value],
            'exists' => in_array($type->value, $existingTypes),
        ])->all();
    }

    public function getDocumentHistoryProperty()
    {
        return $this->supplier->legalDocuments
            ->sortByDesc('created_at')
            ->values();
    }

    public function mount(Supplier $supplier, ?SupplierLegalDocument $document = null): void
    {
        if ($document && $document->exists && $document->supplier_id !== $supplier->id) {
            abort(404);
        }

        $this->supplier = $supplier->load('legalDocuments');

        if ($document && $document->exists) {
            $this->document = $document;
            $this->isEdit = true;
            $this->document_date = $document->document_date ? Carbon::parse($document->document_date)->format('Y-m-d') : '';
            $this->valid_until = $document->valid_until ? Carbon::parse($document->valid_until)->format('Y-m-d') : null;
            $this->noExpiry = is_null($document->valid_until);
            $this->document_type = $document->document_type->value;
            $this->document_number = (string) $document->document_number;
            $this->notary_name = $document->notary_name;
            $this->issuer = $document->issuer;
        }
    }

    public function updatedNoExpiry(bool $value): void
    {
        if ($value) {
            $this->valid_until = null;
            $this->resetValidation('valid_until');
        }
    }

    protected function rules(): array
    {
        return [
            'document_type' => [
                'required',
                Rule::enum(DocumentType::class),
                Rule::unique('supplier_legal_documents', 'document_type')
                    ->where('supplier_id', $this->supplier->id)
                    ->ignore($this->document?->id),
            ],
            'document_number' => 'required|string|max:255',
            'document_date' => 'required|date',
            'notary_name' => 'nullable|string|max:255',
            'issuer' => 'nullable|string|max:255',
            'valid_until' => 'nullable|date|after_or_equal:document_date',
        ];
    }

    public function save()
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->isAdmin()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki otoritas untuk mengelola dokumen supplier.');
        }

        if ($this->noExpiry) {
            $this->valid_until = null;
        }

        if ($this->valid_until !== null && trim($this->valid_until) === '') {
            $this->valid_until = null;
        }

        $validated = $this->validate();

        if ($this->isEdit) {
            $this->document->update($validated);
            $message = 'Dokumen legal berhasil diperbarui.';
        } else {
            $this->supplier->legalDocuments()->create($validated);
            $message = "Dokumen legal berhasil ditambahkan ke supplier {$this->supplier->company_name}.";
        }

        $this->success($message);

        $url = auth()->user()->isSuperAdmin()
            ? route('superadmin.suppliers.edit', $this->supplier->id)
            : route('admin.suppliers.edit', $this->supplier->id);

        return $this->redirect($url, navigate: true);
    }

    public function render()
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->isAdmin()) {
            abort(403);
        }

        return view('livewire.supplier.legal-document-form', [
            'requiredDocuments' => $this->requiredDocuments,
            'documentHistory' => $this->documentHistory,
        ])->layout('layouts.app');
    }
}
