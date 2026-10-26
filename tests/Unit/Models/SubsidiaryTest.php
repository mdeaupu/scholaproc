<?php

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\Subsidiary;
use App\Models\SubsidiaryLegalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Subsidiary model', function () {
    it('has legal document and procurement relationships', function () {
        $subsidiary = Subsidiary::factory()->create();
        $document = SubsidiaryLegalDocument::factory()->for($subsidiary)->create();
        $request = ProcurementRequest::factory()->for($subsidiary, 'subsidiary')->create();

        expect($subsidiary->legalDocuments->first()->id)->toBe($document->id)
            ->and($subsidiary->procurementRequests->first()->id)->toBe($request->id)
            ->and($request->subsidiary->is($subsidiary))->toBeTrue();
    });

    it('finds an active business permit', function () {
        $subsidiary = Subsidiary::factory()->create();
        $active = SubsidiaryLegalDocument::factory()
            ->for($subsidiary)
            ->businessPermit()
            ->create(['valid_until' => now()->addMonth()]);

        expect($subsidiary->activeBusinessPermit()?->id)->toBe($active->id);
    });

    it('calculates total value for assigned projects', function () {
        $subsidiary = Subsidiary::factory()->create();
        $request = ProcurementRequest::factory()->for($subsidiary, 'subsidiary')->create();
        ProcurementRequestItem::factory()->for($request)->create([
            'quantity' => 5,
            'estimated_price' => 200000,
            'official_price' => 200000,
        ]);

        expect((float) $subsidiary->totalProjectValue())->toBe(1000000.0);
    });

    it('cannot be permanently deleted while used by a transaction', function () {
        $subsidiary = Subsidiary::factory()->create();
        ProcurementRequest::factory()->for($subsidiary, 'subsidiary')->create();

        expect(fn () => $subsidiary->delete())
            ->toThrow(LogicException::class);
    });

    it('validates subsidiary status against the supported values', function () {
        $subsidiary = Subsidiary::factory()->create();

        expect(fn () => $subsidiary->changeStatus('pending'))
            ->toThrow(ValidationException::class);

        expect(fn () => $subsidiary->update(['status' => 'pending']))
            ->toThrow(ValidationException::class);
    });

    it('records the actor and before and after values when subsidiary data changes', function () {
        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin);

        $subsidiary = Subsidiary::factory()->create([
            'name' => 'Nama Awal',
            'npwp' => '123456789012345',
        ]);
        $subsidiary->update(['name' => 'Nama Baru']);

        $activity = Activity::query()
            ->where('subject_type', Subsidiary::class)
            ->where('subject_id', $subsidiary->id)
            ->where('description', 'Data Anak Perusahaan diubah')
            ->latest('id')
            ->firstOrFail();

        expect($activity->causer_id)->toBe($admin->id)
            ->and($activity->created_at)->not->toBeNull()
            ->and($activity->properties['before']['name'])->toBe('Nama Awal')
            ->and($activity->properties['after']['name'])->toBe('Nama Baru')
            ->and($activity->properties['changed_at'])->not->toBeEmpty();

        $createdActivity = Activity::query()
            ->where('subject_type', Subsidiary::class)
            ->where('subject_id', $subsidiary->id)
            ->where('description', 'Anak Perusahaan ditambahkan')
            ->firstOrFail();

        expect($createdActivity->causer_id)->toBe($admin->id)
            ->and($createdActivity->created_at)->not->toBeNull()
            ->and($createdActivity->properties['before'])->toBe([])
            ->and($createdActivity->properties['after']['name'])->toBe('Nama Awal');
    });
});
