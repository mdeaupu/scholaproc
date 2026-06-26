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

    public bool $isEdit = false;

    public function getDocumentTypesProperty(): array
    {
        $existingTypes = $this->supplier->legalDocuments()
            ->pluck('document_type')
            ->map(fn($type) => $type instanceof DocumentType ? $type->value : $type)
            ->toArray();

        if ($this->isEdit && $this->document) {
            $currentType = $this->document->document_type instanceof DocumentType
                ? $this->document->document_type->value
                : $this->document->document_type;

            $existingTypes = array_diff($existingTypes, [$currentType]);
        }

        $options = [];
        foreach (DocumentType::cases() as $case) {
            if (!in_array($case->value, $existingTypes)) {
                $options[] = ['id' => $case->value, 'name' => $case->label()];
            }
        }

        return $options;
    }

    public function mount(Supplier $supplier, ?SupplierLegalDocument $document = null): void
    {
        $this->supplier = $supplier;

        if ($document && $document->exists) {
            $this->document = $document;
            $this->isEdit = true;
            $this->document_date = $document->document_date ? Carbon::parse($document->document_date)->format('Y-m-d') : '';
            $this->valid_until = $document->valid_until ? Carbon::parse($document->valid_until)->format('Y-m-d') : null;
            $this->document_type = $document->document_type->value;
            $this->document_number = $document->document_number;
            $this->notary_name = $document->notary_name;
            $this->issuer = $document->issuer;
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
                    ->ignore($this->document?->id)
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
        if (!auth()->user()->isOwner() && !auth()->user()->isAdminCv()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki otoritas untuk mengelola dokumen supplier.');
        }

        if (trim($this->valid_until) === '') {
            $this->valid_until = null;
        }

        $validated = $this->validate();

        if ($this->isEdit) {
            $this->document->update($validated);
            $message = "Dokumen legal berhasil diperbarui.";
        } else {
            $this->supplier->legalDocuments()->create($validated);
            $message = "Dokumen legal berhasil ditambahkan ke supplier {$this->supplier->company_name}.";
        }

        session()->flash('toast_success', $message);

        $route = auth()->user()->isOwner() ? 'owner.suppliers.index' : 'cv.suppliers.index';
        return $this->redirectRoute($route, navigate: true);
    }

    public function render()
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isAdminCv()) {
            abort(403);
        }

        return view('livewire.supplier.legal-document-form')->layout('layouts.app');
    }
}
