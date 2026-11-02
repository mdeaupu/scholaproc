<?php

use App\Livewire\Subsidiary\SubsidiaryIndex;
use App\Models\ProcurementRequest;
use App\Models\Subsidiary;
use App\Models\SubsidiaryLegalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

describe('SubsidiaryIndex Livewire Component', function () {
    it('renders the management page for a superadmin', function () {
        $this->actingAs(User::factory()->superAdmin()->create());

        Livewire::test(SubsidiaryIndex::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.subsidiary.subsidiary-index')
            ->assertSee('Manajemen Anak Perusahaan');
    });

    it('blocks non-superadmin users', function () {
        foreach ([User::factory()->admin()->create(), User::factory()->school()->create()] as $user) {
            $this->actingAs($user)
                ->get(route('superadmin.subsidiaries.index'))
                ->assertForbidden();
        }
    });

    it('searches subsidiary name and address', function () {
        $this->actingAs(User::factory()->superAdmin()->create());
        Subsidiary::factory()->create(['name' => 'PT Anak Jaya', 'address' => 'Jalan Utama']);
        Subsidiary::factory()->create(['name' => 'CV Mitra Baru', 'address' => 'Jalan Lain']);

        Livewire::test(SubsidiaryIndex::class)
            ->set('search', 'Anak Jaya')
            ->assertSee('PT Anak Jaya')
            ->assertDontSee('CV Mitra Baru');
    });

    it('shows subsidiary detail and legal metadata', function () {
        $this->actingAs(User::factory()->superAdmin()->create());
        $subsidiary = Subsidiary::factory()->create();
        $document = SubsidiaryLegalDocument::factory()->for($subsidiary)->deed()->create();

        Livewire::test(SubsidiaryIndex::class)
            ->call('show', $subsidiary->id)
            ->assertSet('detailModal', true)
            ->assertSet('selectedSubsidiary.id', $subsidiary->id)
            ->assertSee($subsidiary->name)
            ->assertSee($document->document_number);
    });

    it('deletes an unused subsidiary after confirmation and audits the deletion', function () {
        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin);
        $subsidiary = Subsidiary::factory()->create(['name' => 'Anak Perusahaan Dihapus']);

        Livewire::test(SubsidiaryIndex::class)
            ->assertSee('Hapus')
            ->assertDontSee('Nonaktifkan')
            ->call('confirmDestroy', $subsidiary->id)
            ->assertSet('confirmingDestroy', true)
            ->assertSet('targetSubsidiaryName', 'Anak Perusahaan Dihapus')
            ->call('destroy')
            ->assertSet('confirmingDestroy', false);

        $activity = Activity::query()
            ->where('subject_type', Subsidiary::class)
            ->where('subject_id', $subsidiary->id)
            ->where('description', 'Anak Perusahaan dihapus')
            ->latest('id')
            ->firstOrFail();

        expect($activity->causer_id)->toBe($admin->id)
            ->and($activity->properties['before']['name'])->toBe('Anak Perusahaan Dihapus')
            ->and($activity->properties['after'])->toBe([])
            ->and($activity->properties['changed_at'])->not->toBeEmpty();
        $this->assertDatabaseMissing('subsidiaries', ['id' => $subsidiary->id]);
    });

    it('does not offer deletion confirmation for a subsidiary already used by a transaction', function () {
        $this->actingAs(User::factory()->superAdmin()->create());
        $subsidiary = Subsidiary::factory()->create();
        ProcurementRequest::factory()->for($subsidiary, 'subsidiary')->create();

        Livewire::test(SubsidiaryIndex::class)
            ->call('confirmDestroy', $subsidiary->id)
            ->assertSet('confirmingDestroy', false);

        $this->assertDatabaseHas('subsidiaries', ['id' => $subsidiary->id]);
    });

    it('retains a subsidiary that has legal documents instead of deleting related records', function () {
        $this->actingAs(User::factory()->superAdmin()->create());
        $subsidiary = Subsidiary::factory()->create();
        SubsidiaryLegalDocument::factory()->for($subsidiary)->deed()->create();

        Livewire::test(SubsidiaryIndex::class)
            ->call('confirmDestroy', $subsidiary->id)
            ->assertSet('confirmingDestroy', false);

        $this->assertDatabaseHas('subsidiaries', ['id' => $subsidiary->id]);
        $this->assertDatabaseHas('subsidiary_legal_documents', ['subsidiary_id' => $subsidiary->id]);
    });

    it('filters active and inactive subsidiaries', function () {
        $this->actingAs(User::factory()->superAdmin()->create());
        Subsidiary::factory()->create(['name' => 'PT Aktif']);
        Subsidiary::factory()->create(['name' => 'PT Nonaktif', 'status' => 'inactive']);

        Livewire::test(SubsidiaryIndex::class)
            ->set('filterStatus', 'active')
            ->assertSee('PT Aktif')
            ->assertDontSee('PT Nonaktif');

        Livewire::test(SubsidiaryIndex::class)
            ->set('filterStatus', 'inactive')
            ->assertSee('PT Nonaktif')
            ->assertDontSee('PT Aktif');
    });

});
