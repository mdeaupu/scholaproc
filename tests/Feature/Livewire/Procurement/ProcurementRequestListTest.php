<?php

use App\Livewire\Procurement\ProcurementRequestList;
use App\Models\BudgetYear;
use App\Models\FundingSource;
use App\Models\ItemUnit;
use App\Models\PackageCategory;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('school sees only its requests and summary values', function () {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $ownRequest = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);
    $otherRequest = ProcurementRequest::factory()->create([
        'school_id' => $otherSchool->id,
        'status' => ProcurementRequest::STATUS_COMPLETED,
    ]);

    Livewire::actingAs($user)
        ->test(ProcurementRequestList::class)
        ->assertSee('PGN-' . $ownRequest->created_at->format('Y') . '-' . str_pad((string) $ownRequest->id, 4, '0', STR_PAD_LEFT))
        ->assertDontSee('PGN-' . $otherRequest->created_at->format('Y') . '-' . str_pad((string) $otherRequest->id, 4, '0', STR_PAD_LEFT))
        ->assertSee('Total Pengajuan')
        ->assertSee('Draft');
});

test('filters school requests by status budget year and funding source', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $year = BudgetYear::factory()->create();
    $source = FundingSource::factory()->create();
    $category = PackageCategory::factory()->create(['name' => 'Peralatan Laboratorium']);
    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'budget_year_id' => $year->id,
        'funding_source_id' => $source->id,
        'package_category_id' => $category->id,
        'status' => ProcurementRequest::STATUS_PRICE_AGREED,
    ]);
    $draftRequest = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);

    Livewire::actingAs($user)
        ->test(ProcurementRequestList::class)
        ->set('filterStatus', ProcurementRequest::STATUS_PRICE_AGREED)
        ->set('filterBudgetYear', (string) $year->id)
        ->set('filterFundingSource', (string) $source->id)
        ->set('search', 'Peralatan Laboratorium')
        ->assertSee('PGN-' . $request->created_at->format('Y') . '-' . str_pad((string) $request->id, 4, '0', STR_PAD_LEFT))
        ->assertDontSee('PGN-' . $draftRequest->created_at->format('Y') . '-' . str_pad((string) $draftRequest->id, 4, '0', STR_PAD_LEFT));
});

test('shows estimated total as rupiah and master data labels', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $category = PackageCategory::factory()->create(['name' => 'Alat Tulis']);
    $year = BudgetYear::factory()->create(['name' => 'Tahun 2025']);
    $source = FundingSource::factory()->create(['name' => 'BOS Reguler']);
    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'package_category_id' => $category->id,
        'budget_year_id' => $year->id,
        'funding_source_id' => $source->id,
    ]);
    $unit = ItemUnit::factory()->create();
    ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
        'unit_id' => $unit->id,
        'quantity' => 2,
        'estimated_price' => 125000,
    ]);

    Livewire::actingAs($user)
        ->test(ProcurementRequestList::class)
        ->assertSee('Alat Tulis')
        ->assertSee('BOS Reguler')
        ->assertSee('Tahun 2025')
        ->assertSee('Rp 250.000');
});

test('shows edit for draft and needs revision and delete only for draft', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $draftRequest = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);
    $revisionRequest = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_NEEDS_REVISION,
    ]);

    Livewire::actingAs($user)
        ->test(ProcurementRequestList::class)
        ->assertSee('Detail')
        ->assertSee('Edit Pengajuan')
        ->assertSeeHtml('wire:click="confirmDeleteDraft(' . $draftRequest->id . ')"')
        ->assertDontSeeHtml('wire:click="confirmDeleteDraft(' . $revisionRequest->id . ')"')
        ->assertDontSee('Kirim Pengajuan');

    $submittedSchool = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $submittedSchool->id]);
    $submittedRequest = ProcurementRequest::factory()->create([
        'school_id' => $submittedSchool->id,
        'status' => ProcurementRequest::STATUS_SUBMITTED,
    ]);

    Livewire::actingAs($user)
        ->test(ProcurementRequestList::class)
        ->assertSee('Detail')
        ->assertDontSee('Edit Pengajuan')
        ->assertDontSeeHtml('wire:click="confirmDeleteDraft(' . $submittedRequest->id . ')"')
        ->assertDontSee('Kirim Pengajuan');
});

test('needs revision requests cannot be deleted', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_NEEDS_REVISION,
    ]);
    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
    ]);

    Livewire::actingAs($user)
        ->test(ProcurementRequestList::class)
        ->assertSee('Edit Pengajuan')
        ->assertDontSeeHtml('wire:click="confirmDeleteDraft(' . $request->id . ')"');

    expect(fn () => Livewire::actingAs($user)
        ->test(ProcurementRequestList::class)
        ->call('delete', $request->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect($request->fresh()->status)->toBe(ProcurementRequest::STATUS_NEEDS_REVISION)
        ->and($item->fresh())->not->toBeNull();
});

test('deleting a draft soft deletes its items and request', function () {
    $school = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $request = ProcurementRequest::factory()->create([
        'school_id' => $school->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);
    $item = ProcurementRequestItem::factory()->create([
        'procurement_request_id' => $request->id,
    ]);

    Livewire::actingAs($user)
        ->test(ProcurementRequestList::class)
        ->call('confirmDeleteDraft', $request->id)
        ->assertSet('confirmingDeleteDraft', true)
        ->assertSee('PGN-' . $request->created_at->format('Y') . '-' . str_pad((string) $request->id, 4, '0', STR_PAD_LEFT))
        ->assertSee('Ya, Hapus')
        ->call('deleteConfirmed')
        ->assertSet('confirmingDeleteDraft', false);

    $this->assertSoftDeleted('procurement_requests', ['id' => $request->id]);
    $this->assertSoftDeleted('procurement_request_items', ['id' => $item->id]);
});

test('school cannot delete or view another school request', function () {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = User::factory()->school()->create(['school_id' => $school->id]);
    $request = ProcurementRequest::factory()->create([
        'school_id' => $otherSchool->id,
        'status' => ProcurementRequest::STATUS_DRAFT,
    ]);

    expect(fn () => Livewire::actingAs($user)
        ->test(ProcurementRequestList::class)
        ->call('delete', $request->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    $this->actingAs($user)
        ->get(route('procurement.show', $request->id))
        ->assertNotFound();

    expect($request->fresh())->not->toBeNull();
});
