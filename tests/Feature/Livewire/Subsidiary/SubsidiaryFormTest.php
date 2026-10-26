<?php

use App\Livewire\Subsidiary\SubsidiaryForm;
use App\Models\ProcurementRequest;
use App\Models\Subsidiary;
use App\Models\SubsidiaryLegalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

describe('SubsidiaryForm Livewire Component', function () {
    it('renders create form for a superadmin with a letterhead preview', function () {
        $this->actingAs(User::factory()->superAdmin()->create());

        Livewire::test(SubsidiaryForm::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.subsidiary.subsidiary-form')
            ->assertSet('isEdit', false)
            ->assertSee('Pratinjau Kop Surat');
    });

    it('loads an existing subsidiary for editing', function () {
        $this->actingAs(User::factory()->superAdmin()->create());
        $subsidiary = Subsidiary::factory()->create();

        Livewire::test(SubsidiaryForm::class, ['subsidiary' => $subsidiary])
            ->assertSet('isEdit', true)
            ->assertSet('name', $subsidiary->name)
            ->assertSet('npwp', $subsidiary->npwp);
    });

    it('shows legal and transaction history when editing', function () {
        $this->actingAs(User::factory()->superAdmin()->create());
        $subsidiary = Subsidiary::factory()->create();
        SubsidiaryLegalDocument::factory()->for($subsidiary)->deed()->create();
        ProcurementRequest::factory()->for($subsidiary, 'subsidiary')->create();

        Livewire::test(SubsidiaryForm::class, ['subsidiary' => $subsidiary])
            ->assertSee('Informasi Legalitas')
            ->assertSee('Riwayat Transaksi')
            ->assertSee('Nama Anak Perusahaan');
    });

    it('creates a subsidiary with the required legal identity', function () {
        $this->actingAs(User::factory()->superAdmin()->create());
        $data = Subsidiary::factory()->make();

        Livewire::test(SubsidiaryForm::class)
            ->set('name', $data->name)
            ->set('npwp', $data->npwp)
            ->set('director_name', $data->director_name)
            ->set('address', $data->address)
            ->call('save')
            ->assertRedirect(route('superadmin.subsidiaries.index'));

        $this->assertDatabaseHas('subsidiaries', [
            'name' => $data->name,
            'npwp' => $data->npwp,
            'director_name' => $data->director_name,
            'status' => 'active',
        ]);
    });

    it('updates an existing subsidiary', function () {
        $this->actingAs(User::factory()->superAdmin()->create());
        $subsidiary = Subsidiary::factory()->create();

        Livewire::test(SubsidiaryForm::class, ['subsidiary' => $subsidiary])
            ->set('name', 'Nama Anak Perusahaan Baru')
            ->call('save')
            ->assertRedirect(route('superadmin.subsidiaries.index'));

        $this->assertDatabaseHas('subsidiaries', [
            'id' => $subsidiary->id,
            'name' => 'Nama Anak Perusahaan Baru',
        ]);
    });

    it('requires a unique NPWP, name, and director', function () {
        $this->actingAs(User::factory()->superAdmin()->create());
        $existing = Subsidiary::factory()->create(['npwp' => '1234567890']);

        Livewire::test(SubsidiaryForm::class)
            ->set('name', '')
            ->set('npwp', $existing->npwp)
            ->set('director_name', '')
            ->call('save')
            ->assertHasErrors(['name', 'npwp', 'director_name']);
    });

    it('allows an existing subsidiary to retain its NPWP', function () {
        $this->actingAs(User::factory()->superAdmin()->create());
        $subsidiary = Subsidiary::factory()->create();

        Livewire::test(SubsidiaryForm::class, ['subsidiary' => $subsidiary])
            ->set('name', 'Nama Diperbarui')
            ->call('save')
            ->assertHasNoErrors();
    });

    it('manages active status from the edit form', function () {
        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin);
        $subsidiary = Subsidiary::factory()->create();

        $component = Livewire::test(SubsidiaryForm::class, ['subsidiary' => $subsidiary])
            ->assertSee('Status Anak Perusahaan')
            ->assertSee('Nonaktifkan')
            ->call('confirmDeactivate')
            ->assertSet('confirmingStatusDeactivation', true)
            ->call('deactivate')
            ->assertSet('status', 'inactive')
            ->assertSee('Aktifkan');

        expect($subsidiary->fresh()->status)->toBe('inactive');

        $activity = Activity::query()
            ->where('subject_type', Subsidiary::class)
            ->where('subject_id', $subsidiary->id)
            ->where('description', 'Status Anak Perusahaan diubah')
            ->latest('id')
            ->firstOrFail();

        expect($activity->causer_id)->toBe($admin->id)
            ->and($activity->properties['before']['status'])->toBe('active')
            ->and($activity->properties['after']['status'])->toBe('inactive');

        $component->call('activate')->assertSet('status', 'active');

        expect($subsidiary->fresh()->status)->toBe('active');
    });

    it('blocks admin and school users from managing subsidiaries', function () {
        foreach ([User::factory()->admin()->create(), User::factory()->school()->create()] as $user) {
            $this->actingAs($user);

            Livewire::test(SubsidiaryForm::class)
                ->assertStatus(403);

            $this->get(route('superadmin.subsidiaries.create'))
                ->assertForbidden();
        }
    });

    it('does not register an admin route for subsidiary management', function () {
        expect(Route::has('admin.subsidiaries.index'))->toBeFalse();
    });
});
