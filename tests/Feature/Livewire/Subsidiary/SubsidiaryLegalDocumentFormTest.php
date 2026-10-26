<?php

use App\Livewire\Subsidiary\SubsidiaryLegalDocumentForm;
use App\Models\Subsidiary;
use App\Models\SubsidiaryLegalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('SubsidiaryLegalDocumentForm Livewire Component', function () {
    it('renders the create form for a superadmin', function () {
        $subsidiary = Subsidiary::factory()->create();
        $this->actingAs(User::factory()->superAdmin()->create());

        Livewire::test(SubsidiaryLegalDocumentForm::class, ['subsidiary' => $subsidiary])
            ->assertStatus(200)
            ->assertViewIs('livewire.subsidiary.subsidiary-legal-document-form')
            ->assertSee($subsidiary->name)
            ->assertSee('Catat metadata legalitas');
    });

    it('loads a legal document for editing', function () {
        $subsidiary = Subsidiary::factory()->create();
        $document = SubsidiaryLegalDocument::factory()->for($subsidiary)->create();
        $this->actingAs(User::factory()->superAdmin()->create());

        Livewire::test(SubsidiaryLegalDocumentForm::class, [
            'subsidiary' => $subsidiary,
            'document' => $document,
        ])
            ->assertSet('isEdit', true)
            ->assertSet('document_type', $document->document_type->value)
            ->assertSet('document_number', $document->document_number);
    });

    it('returns 404 when the document belongs to another subsidiary', function () {
        $subsidiaryA = Subsidiary::factory()->create();
        $subsidiaryB = Subsidiary::factory()->create();
        $document = SubsidiaryLegalDocument::factory()->for($subsidiaryB)->create();
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->get(route('superadmin.subsidiaries.legal-documents.edit', [$subsidiaryA->id, $document->id]))
            ->assertNotFound();
    });

    it('creates legal metadata and redirects to the subsidiary', function () {
        $subsidiary = Subsidiary::factory()->create();
        $this->actingAs(User::factory()->superAdmin()->create());

        Livewire::test(SubsidiaryLegalDocumentForm::class, ['subsidiary' => $subsidiary])
            ->set('document_type', 'business_permit')
            ->set('document_number', 'NIB-12345')
            ->set('document_date', now()->format('Y-m-d'))
            ->set('issuer', 'OSS')
            ->call('save')
            ->assertRedirect(route('superadmin.subsidiaries.edit', $subsidiary->id));

        $this->assertDatabaseHas('subsidiary_legal_documents', [
            'subsidiary_id' => $subsidiary->id,
            'document_number' => 'NIB-12345',
            'issuer' => 'OSS',
        ]);
    });

    it('updates existing legal metadata', function () {
        $subsidiary = Subsidiary::factory()->create();
        $document = SubsidiaryLegalDocument::factory()->for($subsidiary)->create();
        $this->actingAs(User::factory()->superAdmin()->create());

        Livewire::test(SubsidiaryLegalDocumentForm::class, [
            'subsidiary' => $subsidiary,
            'document' => $document,
        ])
            ->set('document_number', 'UPDATED-999')
            ->call('save')
            ->assertRedirect(route('superadmin.subsidiaries.edit', $subsidiary->id));

        $this->assertDatabaseHas('subsidiary_legal_documents', [
            'id' => $document->id,
            'document_number' => 'UPDATED-999',
        ]);
    });

    it('validates the legal document validity date', function () {
        $subsidiary = Subsidiary::factory()->create();
        $this->actingAs(User::factory()->superAdmin()->create());

        Livewire::test(SubsidiaryLegalDocumentForm::class, ['subsidiary' => $subsidiary])
            ->set('document_type', 'deed')
            ->set('document_number', 'DOC-123')
            ->set('document_date', now()->format('Y-m-d'))
            ->set('valid_until', now()->subDay()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors(['valid_until']);
    });

    it('clears the validity date for documents without expiry', function () {
        $subsidiary = Subsidiary::factory()->create();
        $this->actingAs(User::factory()->superAdmin()->create());

        Livewire::test(SubsidiaryLegalDocumentForm::class, ['subsidiary' => $subsidiary])
            ->set('document_type', 'business_permit')
            ->set('document_number', 'NIB-999')
            ->set('document_date', now()->format('Y-m-d'))
            ->set('valid_until', now()->addYear()->format('Y-m-d'))
            ->set('noExpiry', true)
            ->call('save')
            ->assertRedirect(route('superadmin.subsidiaries.edit', $subsidiary->id));

        $this->assertDatabaseHas('subsidiary_legal_documents', [
            'document_number' => 'NIB-999',
            'valid_until' => null,
        ]);
    });

    it('blocks admin users from legal document management', function () {
        $subsidiary = Subsidiary::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SubsidiaryLegalDocumentForm::class, ['subsidiary' => $subsidiary])
            ->assertStatus(403);
    });
});
