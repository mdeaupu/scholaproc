<?php

use App\Models\ProcurementRequest;
use App\Models\Subsidiary;
use App\Models\TaxRule;
use App\Models\User;
use App\Services\TaxEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('tax evaluation returns NEEDS_REVIEW when no active rule exists', function () {
    $request = ProcurementRequest::factory()->create();

    $result = app(TaxEvaluationService::class)->evaluate($request);

    expect($result['status'])->toBe('NEEDS_REVIEW');
});

test('tax evaluation applies active matching rule rates', function () {
    $subsidiary = Subsidiary::factory()->create();
    $request = ProcurementRequest::factory()->create(['subsidiary_id' => $subsidiary->id]);
    $approver = User::factory()->admin()->create();
    $verifier = User::factory()->admin()->create();

    foreach ([
        TaxRule::TAX_PPN => 11,
        TaxRule::TAX_PPH22 => 1.5,
        TaxRule::TAX_PPH23 => 2,
    ] as $taxType => $rate) {
        TaxRule::create([
            'tax_type' => $taxType,
            'name' => strtoupper($taxType).' test',
            'version' => 1,
            'status' => TaxRule::STATUS_ACTIVE,
            'subsidiary_id' => $subsidiary->id,
            'rate' => $rate,
            'calculation_method' => 'percentage',
            'legal_basis' => 'Dasar hukum untuk pengujian.',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);
    }

    $result = app(TaxEvaluationService::class)->evaluate($request);

    expect($result['status'])->toBe('RESOLVED')
        ->and($result['rates']['ppn'])->toBe(11.0)
        ->and($result['rates']['pph22'])->toBe(1.5)
        ->and($result['rates']['pph23'])->toBe(2.0);
});

test('inactive or draft rules are not applied', function () {
    TaxRule::create([
        'tax_type' => TaxRule::TAX_PPN,
        'name' => 'Draft PPN',
        'version' => 1,
        'status' => TaxRule::STATUS_DRAFT,
        'rate' => 11,
    ]);

    $request = ProcurementRequest::factory()->create();

    $result = app(TaxEvaluationService::class)->evaluate($request);

    expect($result['status'])->toBe('NEEDS_REVIEW');
});

test('active tax rule without approval and verification is not resolved', function () {
    $subsidiary = Subsidiary::factory()->create();
    TaxRule::create([
        'tax_type' => TaxRule::TAX_PPN,
        'name' => 'Unverified PPN',
        'status' => TaxRule::STATUS_ACTIVE,
        'subsidiary_id' => $subsidiary->id,
        'rate' => 11,
        'calculation_method' => 'percentage',
        'legal_basis' => 'Dasar hukum pengujian.',
    ]);

    $result = app(TaxEvaluationService::class)->evaluate(
        ProcurementRequest::factory()->create(['subsidiary_id' => $subsidiary->id])
    );

    expect($result['status'])->toBe('NEEDS_REVIEW')
        ->and($result['basis'])->toContain('belum memenuhi validasi');
});

test('activating a rule does not deactivate another active rule of the same type', function () {
    $approver = User::factory()->admin()->create();
    $verifier = User::factory()->admin()->create();
    $rules = collect([1, 2])->map(fn (int $version): TaxRule => TaxRule::create([
        'tax_type' => TaxRule::TAX_PPN,
        'name' => 'PPN '.$version,
        'version' => $version,
        'status' => TaxRule::STATUS_APPROVED,
        'rate' => 11,
        'calculation_method' => 'percentage',
        'legal_basis' => 'Dasar hukum pengujian.',
        'approved_by' => $approver->id,
        'approved_at' => now(),
    ]));
    $rules[0]->forceFill([
        'status' => TaxRule::STATUS_ACTIVE,
        'verified_by' => $verifier->id,
        'verified_at' => now(),
    ])->save();

    $rules[1]->activate(User::factory()->admin()->create()->id);

    expect($rules[0]->fresh()->status)->toBe(TaxRule::STATUS_ACTIVE)
        ->and($rules[1]->fresh()->status)->toBe(TaxRule::STATUS_ACTIVE);
});

test('multiple matching active rules require review rather than choosing a rate', function () {
    $subsidiary = Subsidiary::factory()->create();
    $approver = User::factory()->admin()->create();
    $verifier = User::factory()->admin()->create();

    foreach ([1, 2] as $version) {
        TaxRule::create([
            'tax_type' => TaxRule::TAX_PPN,
            'name' => 'PPN '.$version,
            'version' => $version,
            'status' => TaxRule::STATUS_ACTIVE,
            'subsidiary_id' => $subsidiary->id,
            'rate' => $version * 5,
            'calculation_method' => 'percentage',
            'legal_basis' => 'Dasar hukum pengujian.',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);
    }

    $result = app(TaxEvaluationService::class)->evaluate(
        ProcurementRequest::factory()->create(['subsidiary_id' => $subsidiary->id])
    );

    expect($result['status'])->toBe('NEEDS_REVIEW')
        ->and($result['rates'][TaxRule::TAX_PPN])->toBeNull()
        ->and($result['basis'])->toContain('Konflik aturan pajak aktif');
});

test('tax evaluation cannot be redirected to another subsidiary through context', function () {
    $subsidiary = Subsidiary::factory()->create();
    $otherSubsidiary = Subsidiary::factory()->create();
    $approver = User::factory()->admin()->create();
    $verifier = User::factory()->admin()->create();

    foreach ([
        $subsidiary->id => [5, 1, 2],
        $otherSubsidiary->id => [10, 3, 4],
    ] as $subsidiaryId => $rates) {
        foreach ([TaxRule::TAX_PPN, TaxRule::TAX_PPH22, TaxRule::TAX_PPH23] as $index => $taxType) {
            TaxRule::create([
                'tax_type' => $taxType,
                'name' => 'Tax '.$subsidiaryId.' '.$taxType,
                'version' => 1,
                'status' => TaxRule::STATUS_ACTIVE,
                'subsidiary_id' => $subsidiaryId,
                'rate' => $rates[$index],
                'calculation_method' => 'percentage',
                'legal_basis' => 'Dasar hukum pengujian.',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'verified_by' => $verifier->id,
                'verified_at' => now(),
            ]);
        }
    }

    $result = app(TaxEvaluationService::class)->evaluate(
        ProcurementRequest::factory()->create(['subsidiary_id' => $subsidiary->id]),
        ['subsidiary_id' => $otherSubsidiary->id]
    );

    expect($result['status'])->toBe('RESOLVED')
        ->and($result['rates'][TaxRule::TAX_PPN])->toBe(5.0)
        ->and($result['context']['subsidiary_id'])->toBe($subsidiary->id);
});
