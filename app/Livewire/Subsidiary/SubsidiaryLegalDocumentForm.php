<?php

namespace App\Livewire\Subsidiary;

use App\Enums\DocumentType;
use App\Models\Subsidiary;
use App\Models\SubsidiaryLegalDocument;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Mary\Traits\Toast;

class SubsidiaryLegalDocumentForm extends Component
{
    use Toast;

    public Subsidiary $subsidiary;

    public ?SubsidiaryLegalDocument $document = null;

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
        $existingTypes = $this->subsidiary->legalDocuments
            ->pluck('document_type')
            ->map(fn ($type) => $type instanceof DocumentType ? $type->value : $type)
            ->toArray();

        if ($this->isEdit && $this->document) {
            $currentType = $this->document->document_type instanceof DocumentType
                ? $this->document->document_type->value
                : $this->document->document_type;

            $existingTypes = array_diff($existingTypes, [$currentType]);
        }

        return collect(DocumentType::cases())
            ->reject(fn (DocumentType $type) => in_array($type->value, $existingTypes))
            ->map(fn (DocumentType $type) => ['id' => $type->value, 'name' => $type->label()])
            ->values()
            ->all();
    }

    public function getRequiredDocumentsProperty(): array
    {
        $existingTypes = $this->subsidiary->legalDocuments
            ->map(fn ($document) => $document->document_type instanceof DocumentType
                ? $document->document_type->value
                : $document->document_type)
            ->toArray();

        $descriptions = [
            DocumentType::DEED->value => 'Identitas hukum tambahan bila dibutuhkan.',
            DocumentType::NPWP->value => 'Dokumen pendukung data perpajakan bila dibutuhkan.',
            DocumentType::BUSINESS_PERMIT->value => 'Nomor izin usaha seperti NIB bila dibutuhkan.',
            DocumentType::OTHER->value => 'Dokumen legal pendukung lainnya.',
        ];

        return collect(DocumentType::cases())->map(fn (DocumentType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $descriptions[$type->value],
            'exists' => in_array($type->value, $existingTypes),
        ])->all();
    }

    public function getDocumentHistoryProperty()
    {
        return $this->subsidiary->legalDocuments
            ->sortByDesc('created_at')
            ->values();
    }

    public function mount(Subsidiary $subsidiary, ?SubsidiaryLegalDocument $document = null): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if ($document && $document->exists && $document->subsidiary_id !== $subsidiary->id) {
            abort(404);
        }

        $this->subsidiary = $subsidiary->load('legalDocuments');

        if ($document && $document->exists) {
            $this->document = $document;
            $this->isEdit = true;
            $this->document_date = $document->document_date
                ? Carbon::parse($document->document_date)->format('Y-m-d')
                : '';
            $this->valid_until = $document->valid_until
                ? Carbon::parse($document->valid_until)->format('Y-m-d')
                : null;
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
                Rule::unique('subsidiary_legal_documents', 'document_type')
                    ->where('subsidiary_id', $this->subsidiary->id)
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
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Hanya Superadmin yang dapat mengelola legalitas Anak Perusahaan.');

        if ($this->noExpiry || ($this->valid_until !== null && trim($this->valid_until) === '')) {
            $this->valid_until = null;
        }

        $validated = $this->validate();

        if ($this->isEdit) {
            $this->document->update($validated);
            $message = 'Data legalitas berhasil diperbarui.';
        } else {
            $this->subsidiary->legalDocuments()->create($validated);
            $message = "Data legalitas Anak Perusahaan {$this->subsidiary->name} berhasil ditambahkan.";
        }

        $this->success($message);

        return $this->redirectRoute(
            'superadmin.subsidiaries.edit',
            ['subsidiary' => $this->subsidiary->id],
            navigate: true
        );
    }

    public function render()
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        return view('livewire.subsidiary.subsidiary-legal-document-form', [
            'requiredDocuments' => $this->requiredDocuments,
            'documentHistory' => $this->documentHistory,
        ])->layout('layouts.app');
    }
}
