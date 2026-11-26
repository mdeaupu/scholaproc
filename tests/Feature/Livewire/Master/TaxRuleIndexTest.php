<?php

use App\Livewire\Master\TaxRuleIndex;
use App\Livewire\Procurement\ProcurementReviewPanel;
use App\Models\FundingSource;
use App\Models\PackageCategory;
use App\Models\ProcurementRequest;
use App\Models\Subsidiary;
use App\Models\TaxRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createInactiveTaxRule(array $overrides = []): TaxRule
{
    return TaxRule::create(array_merge([
        'tax_type' => TaxRule::TAX_PPN,
        'name' => 'PPN nonaktif',
        'status' => TaxRule::STATUS_INACTIVE,
        'rate' => 11,
        'calculation_method' => 'percentage',
        'legal_basis' => 'Dasar hukum untuk pengujian.',
        'created_by' => User::factory()->superAdmin()->create()->id,
    ], $overrides));
}

function activeTaxRule(array $overrides = []): TaxRule
{
    $approver = User::factory()->admin()->create();
    $verifier = User::factory()->admin()->create();

    return TaxRule::create(array_merge([
        'tax_type' => TaxRule::TAX_PPN,
        'name' => 'PPN aktif',
        'status' => TaxRule::STATUS_ACTIVE,
        'rate' => 11,
        'calculation_method' => 'percentage',
        'legal_basis' => 'Dasar hukum untuk pengujian.',
        'approved_by' => $approver->id,
        'approved_at' => now(),
        'verified_by' => $verifier->id,
        'verified_at' => now(),
    ], $overrides));
}

test('superadmin dapat mengelola aturan pajak', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(TaxRuleIndex::class)
        ->assertStatus(200)
        ->assertSee('Manajemen Aturan Pajak')
        ->assertSee('Tambah Aturan Pajak')
        ->assertSee('Kelengkapan Aturan Wajib (PPN · PPh 22 · PPh 23)');

    $this->get('/master/tax-rules')
        ->assertOk()
        ->assertSee('Aturan Pajak');
});

test('admin dan sekolah tidak dapat mengakses manajemen aturan pajak', function () {
    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(TaxRuleIndex::class)->assertStatus(403);

    $this->actingAs(User::factory()->school()->create());
    Livewire::test(TaxRuleIndex::class)->assertStatus(403);
});

test('aturan pajak baru dibuat aktif dengan jejak pengaktif', function () {
    $superadmin = User::factory()->superAdmin()->create();
    $this->actingAs($superadmin);

    Livewire::test(TaxRuleIndex::class)
        ->assertDontSee('buyer_type', false)
        ->assertDontSee('pkp_status', false)
        ->assertDontSee('item_type', false)
        ->call('openModal')
        ->set('tax_type', TaxRule::TAX_PPN)
        ->set('name', 'PPN umum 11%')
        ->set('rate', '11')
        ->set('legal_basis', 'PMK 131/PMK.010/2024 tentang PPN')
        ->call('save')
        ->assertHasNoErrors();

    $rule = TaxRule::sole();

    expect($rule->status)->toBe(TaxRule::STATUS_ACTIVE)
        ->and($rule->approved_by)->toBe($superadmin->id)
        ->and($rule->approved_at)->not->toBeNull()
        ->and($rule->verified_by)->toBe($superadmin->id)
        ->and($rule->verified_at)->not->toBeNull()
        ->and($rule->transaction_type)->toBeNull()
        ->and($rule->funding_source)->toBeNull()
        ->and($rule->subsidiary_id)->toBeNull()
        ->and($rule->buyer_type)->toBeNull()
        ->and($rule->pkp_status)->toBeNull()
        ->and($rule->item_type)->toBeNull()
        ->and($rule->dpp_method)->toBe('subtotal')
        ->and($rule->calculation_method)->toBe('percentage')
        ->and($rule->created_by)->toBe($superadmin->id)
        ->and((float) $rule->rate)->toBe(11.0);
});

test('tipe transaksi dan sumber dana memakai nama master', function () {
    PackageCategory::factory()->create(['name' => 'Jasa Konsultasi']);
    FundingSource::factory()->create(['name' => 'Dana BOS']);
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(TaxRuleIndex::class)
        ->call('openModal')
        ->set('tax_type', TaxRule::TAX_PPH23)
        ->set('name', 'PPh 23 jasa 2%')
        ->set('transaction_type', 'Jasa Konsultasi')
        ->set('funding_source', 'Dana BOS')
        ->set('rate', '2')
        ->set('legal_basis', 'UU Ketentuan Umum dan Tata Cara Pajak')
        ->call('save')
        ->assertHasNoErrors();

    $rule = TaxRule::sole();

    expect($rule->transaction_type)->toBe('Jasa Konsultasi')
        ->and($rule->funding_source)->toBe('Dana BOS');

    Livewire::test(TaxRuleIndex::class)
        ->call('openModal')
        ->set('tax_type', TaxRule::TAX_PPH23)
        ->set('name', 'Aturan tanpa tipe valid')
        ->set('transaction_type', 'Tidak Ada di Master')
        ->set('rate', '2')
        ->set('legal_basis', 'Dasar hukum untuk pengujian.')
        ->call('save')
        ->assertHasErrors(['transaction_type']);
});

test('aturan pajak dapat dinonaktifkan dan diaktifkan kembali', function () {
    $superadmin = User::factory()->superAdmin()->create();
    $this->actingAs($superadmin);

    Livewire::test(TaxRuleIndex::class)
        ->call('openModal')
        ->set('tax_type', TaxRule::TAX_PPN)
        ->set('name', 'PPN umum 11%')
        ->set('rate', '11')
        ->set('legal_basis', 'PMK 131/PMK.010/2024 tentang PPN')
        ->call('save')
        ->assertHasNoErrors();

    $rule = TaxRule::sole();
    expect($rule->status)->toBe(TaxRule::STATUS_ACTIVE);

    Livewire::test(TaxRuleIndex::class)->call('deactivate', $rule->id);
    expect($rule->fresh()->status)->toBe(TaxRule::STATUS_INACTIVE);

    Livewire::test(TaxRuleIndex::class)->call('activate', $rule->id);
    $fresh = $rule->fresh();

    expect($fresh->status)->toBe(TaxRule::STATUS_ACTIVE)
        ->and($fresh->approved_by)->toBe($superadmin->id)
        ->and($fresh->approved_at)->not->toBeNull()
        ->and($fresh->verified_by)->toBe($superadmin->id)
        ->and($fresh->verified_at)->not->toBeNull();

    Livewire::test(TaxRuleIndex::class)
        ->call('openModal')
        ->set('tax_type', TaxRule::TAX_PPH23)
        ->set('name', 'PPh 23 nonaktif')
        ->set('rate', '2')
        ->set('legal_basis', 'Dasar hukum untuk pengujian.')
        ->set('is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    $inactive = TaxRule::query()->where('name', 'PPh 23 nonaktif')->sole();

    expect($inactive->status)->toBe(TaxRule::STATUS_INACTIVE)
        ->and($inactive->approved_by)->toBeNull()
        ->and($inactive->approved_at)->toBeNull()
        ->and($inactive->verified_by)->toBeNull()
        ->and($inactive->verified_at)->toBeNull();
});

test('aturan pajak aktif dapat diedit dan aturan apa pun dapat dihapus', function () {
    $creator = User::factory()->superAdmin()->create();
    $active = activeTaxRule(['name' => 'PPN aktif']);

    $this->actingAs($creator);
    Livewire::test(TaxRuleIndex::class)
        ->call('openModal', $active->id)
        ->assertSet('modal', true)
        ->assertSet('is_active', true);

    Livewire::test(TaxRuleIndex::class)->call('delete', $active->id);
    $this->assertSoftDeleted('tax_rules', ['id' => $active->id]);

    $inactive = createInactiveTaxRule(['name' => 'PPN nonaktif dihapus']);
    Livewire::test(TaxRuleIndex::class)->call('delete', $inactive->id);
    $this->assertSoftDeleted('tax_rules', ['id' => $inactive->id]);
});

test('indikator kelengkapan menampilkan status setiap anak perusahaan', function () {
    $subsidiary = Subsidiary::factory()->create(['name' => 'PT Contoh']);

    activeTaxRule([
        'name' => 'PPN PT Contoh',
        'subsidiary_id' => $subsidiary->id,
        'rate' => 11,
    ]);
    activeTaxRule([
        'name' => 'PPh 22 tanpa persetujuan',
        'tax_type' => TaxRule::TAX_PPH22,
        'subsidiary_id' => null,
        'rate' => 1,
        'approved_by' => null,
        'approved_at' => null,
        'verified_by' => null,
        'verified_at' => null,
    ]);
    activeTaxRule([
        'name' => 'PPh 23 lintas entitas',
        'tax_type' => TaxRule::TAX_PPH23,
        'subsidiary_id' => null,
        'rate' => 2,
    ]);
    activeTaxRule([
        'name' => 'PPh 22 PT Contoh',
        'tax_type' => TaxRule::TAX_PPH22,
        'subsidiary_id' => $subsidiary->id,
        'rate' => 1,
    ]);

    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(TaxRuleIndex::class)
        ->assertSee('Kelengkapan Aturan Wajib (PPN · PPh 22 · PPh 23)')
        ->assertSee('Lintas Anak Perusahaan')
        ->assertSee('PT Contoh')
        ->assertSee('Belum ada aturan aktif')
        ->assertSee('Belum valid')
        ->assertSee('Aktif · 11%')
        ->assertSee('Aktif · 2%')
        ->assertSee('Konflik (2 aturan)');
});

test('evaluasi aturan pajak pada panel review menjadi RESOLVED dengan aturan aktif lengkap', function () {
    $approver = User::factory()->admin()->create();
    $verifier = User::factory()->admin()->create();
    $actor = User::factory()->admin()->create();
    $subsidiary = Subsidiary::factory()->create();
    $request = ProcurementRequest::factory()->create(['subsidiary_id' => $subsidiary->id]);

    foreach ([
        TaxRule::TAX_PPN => 11,
        TaxRule::TAX_PPH22 => 1.5,
        TaxRule::TAX_PPH23 => 2,
    ] as $taxType => $rate) {
        TaxRule::create([
            'tax_type' => $taxType,
            'name' => strtoupper($taxType).' aktif',
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

    $this->actingAs($actor);

    Livewire::test(ProcurementReviewPanel::class, ['procurementRequest' => $request])
        ->call('evaluateTaxConfiguration');

    $request->refresh();

    expect($request->isTaxConfigurationValidated())->toBeTrue()
        ->and((float) $request->ppn_rate)->toBe(11.0)
        ->and((float) $request->pph_22_rate)->toBe(1.5)
        ->and((float) $request->pph_23_rate)->toBe(2.0)
        ->and($request->tax_configuration_basis)->toContain('Evaluasi otomatis berdasarkan aturan pajak aktif');
});

test('evaluasi pada panel review gagal saat aturan aktif belum lengkap', function () {
    $actor = User::factory()->admin()->create();
    $subsidiary = Subsidiary::factory()->create();
    $request = ProcurementRequest::factory()->create(['subsidiary_id' => $subsidiary->id]);

    $this->actingAs($actor);

    Livewire::test(ProcurementReviewPanel::class, ['procurementRequest' => $request])
        ->call('evaluateTaxConfiguration');

    $request->refresh();

    expect($request->isTaxConfigurationValidated())->toBeFalse()
        ->and($request->tax_configuration_basis)
        ->toContain('Tidak ditemukan aturan pajak aktif yang cocok untuk: ppn, pph22, pph23');
});

test('menyimpan aturan aktif yang tumpang tindih dengan aturan aktif lain diblokir', function () {
    $superadmin = User::factory()->superAdmin()->create();
    $this->actingAs($superadmin);

    activeTaxRule(['name' => 'PPN umum 11%', 'rate' => 11]);

    Livewire::test(TaxRuleIndex::class)
        ->call('openModal')
        ->set('tax_type', TaxRule::TAX_PPN)
        ->set('name', 'PPN umum duplikat')
        ->set('rate', '12')
        ->set('legal_basis', 'Dasar hukum untuk pengujian.')
        ->call('save')
        ->assertHasErrors(['tax_type']);

    expect(TaxRule::query()->where('name', 'PPN umum duplikat')->count())->toBe(0);
});

test('aturan umum (tanpa Anak Perusahaan) tumpang tindih dengan aturan spesifik Anak Perusahaan', function () {
    $superadmin = User::factory()->superAdmin()->create();
    $subsidiary = Subsidiary::factory()->create();
    $this->actingAs($superadmin);

    activeTaxRule(['name' => 'PPN lintas entitas', 'subsidiary_id' => null, 'rate' => 11]);

    Livewire::test(TaxRuleIndex::class)
        ->call('openModal')
        ->set('tax_type', TaxRule::TAX_PPN)
        ->set('name', 'PPN untuk PT Contoh')
        ->set('subsidiary_id', $subsidiary->id)
        ->set('rate', '11')
        ->set('legal_basis', 'Dasar hukum untuk pengujian.')
        ->call('save')
        ->assertHasErrors(['tax_type']);

    expect(TaxRule::query()->where('name', 'PPN untuk PT Contoh')->count())->toBe(0);
});

test('mengubah aturan aktif agar tumpang tindih dengan aturan lain diblokir (ciri sendiri dikecualikan)', function () {
    $superadmin = User::factory()->superAdmin()->create();
    $this->actingAs($superadmin);

    activeTaxRule(['name' => 'PPN A', 'rate' => 11]);
    $ruleB = activeTaxRule(['name' => 'PPN B', 'rate' => 12]);

    Livewire::test(TaxRuleIndex::class)
        ->call('openModal', $ruleB->id)
        ->set('name', 'PPN B diubah')
        ->set('rate', '11')
        ->set('legal_basis', 'Dasar hukum untuk pengujian.')
        ->call('save')
        ->assertHasErrors(['tax_type']);

    expect($ruleB->fresh()->name)->toBe('PPN B');
});

test('aturan aktif dengan konteks berbeda dapat coexist', function () {
    $superadmin = User::factory()->superAdmin()->create();
    $this->actingAs($superadmin);

    PackageCategory::factory()->create(['name' => 'Pembelian Barang']);
    PackageCategory::factory()->create(['name' => 'Pembelian Jasa']);

    activeTaxRule(['name' => 'PPN transaksi A', 'transaction_type' => 'Pembelian Barang', 'rate' => 11]);

    Livewire::test(TaxRuleIndex::class)
        ->call('openModal')
        ->set('tax_type', TaxRule::TAX_PPN)
        ->set('name', 'PPN transaksi B')
        ->set('transaction_type', 'Pembelian Jasa')
        ->set('rate', '11')
        ->set('legal_basis', 'Dasar hukum untuk pengujian.')
        ->call('save')
        ->assertHasNoErrors();

    expect(TaxRule::query()->where('name', 'PPN transaksi B')->count())->toBe(1);
});
