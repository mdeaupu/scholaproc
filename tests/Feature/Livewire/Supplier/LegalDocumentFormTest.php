<?php

use App\Livewire\Supplier\LegalDocumentForm;
use App\Models\Supplier;
use App\Models\SupplierLegalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('LegalDocumentForm Livewire Component', function () {
    it('renders create form for authorized user', function () {
        $user = User::factory()->create(['role' => 'owner']);
        $supplier = Supplier::factory()->create();

        $this->actingAs($user);

        Livewire::test(LegalDocumentForm::class, ['supplier' => $supplier])
            ->assertStatus(200)
            ->assertViewIs('livewire.supplier.legal-document-form')
            ->assertSet('isEdit', false)
            ->assertSee($supplier->company_name);
    });

    it('renders edit form with existing document', function () {
        $user = User::factory()->create(['role' => 'owner']);
        $supplier = Supplier::factory()->create();

        $this->actingAs($user);

        $doc = SupplierLegalDocument::factory()->for($supplier)->create();

        Livewire::test(LegalDocumentForm::class, [
            'supplier' => $supplier,
            'document' => $doc,
        ])
            ->assertSet('isEdit', true)
            ->assertSet('document_type', $doc->document_type->value)
            ->assertSet('document_number', $doc->document_number);
    });

    it('can store new legal document', function () {
        $user = User::factory()->create(['role' => 'owner']);
        $supplier = Supplier::factory()->create();

        $this->actingAs($user);

        $data = [
            'document_type' => 'business_permit',
            'document_number' => 'NIB-12345',
            'document_date' => now()->format('Y-m-d'),
            'issuer' => 'OSS',
            'valid_until' => now()->addYear()->format('Y-m-d'),
        ];

        Livewire::test(LegalDocumentForm::class, ['supplier' => $supplier])
            ->set('document_type', $data['document_type'])
            ->set('document_number', $data['document_number'])
            ->set('document_date', $data['document_date'])
            ->set('issuer', $data['issuer'])
            ->set('valid_until', $data['valid_until'])
            ->call('save')
            ->assertRedirect(route('owner.suppliers.index'));

        $this->assertDatabaseHas('supplier_legal_documents', [
            'supplier_id' => $supplier->id,
            'document_number' => $data['document_number'],
        ]);
    });

    it('can update legal document', function () {
        $user = User::factory()->create(['role' => 'owner']);
        $supplier = Supplier::factory()->create();

        $this->actingAs($user);

        $doc = SupplierLegalDocument::factory()->for($supplier)->create();
        $newNumber = 'UPDATED-999';

        Livewire::test(LegalDocumentForm::class, [
            'supplier' => $supplier,
            'document' => $doc,
        ])
            ->set('document_number', $newNumber)
            ->call('save')
            ->assertRedirect(route('owner.suppliers.index'));

        $this->assertDatabaseHas('supplier_legal_documents', [
            'id' => $doc->id,
            'document_number' => $newNumber,
        ]);
    });

    it('validates that valid_until must be after document_date', function () {
        $user = User::factory()->create(['role' => 'owner']);
        $supplier = Supplier::factory()->create();

        $this->actingAs($user);

        Livewire::test(LegalDocumentForm::class, ['supplier' => $supplier])
            ->set('document_type', 'deed')
            ->set('document_number', 'DOC-123')
            ->set('document_date', now()->format('Y-m-d'))
            ->set('valid_until', now()->subDay()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors(['valid_until']);
    });
});