<?php

namespace App\Livewire\Master;

use App\Models\FundingSource;
use App\Models\PackageCategory;
use App\Models\Subsidiary;
use App\Models\TaxRule;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class TaxRuleIndex extends Component
{
    use Toast, WithPagination;

    public bool $modal = false;

    public ?TaxRule $taxRule = null;

    public string $search = '';

    public string $filterType = '';

    public string $filterStatus = '';

    public string $filterSubsidiary = '';

    public string $tax_type = '';

    public string $name = '';

    public string $subsidiary_id = '';

    public string $transaction_type = '';

    public string $funding_source = '';

    public string $rate = '';

    public string $legal_basis = '';

    public string $effective_from = '';

    public string $effective_to = '';

    public string $change_notes = '';

    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorizeSuperAdmin();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterSubsidiary(): void
    {
        $this->resetPage();
    }

    public function openModal(?TaxRule $taxRule = null): void
    {
        $this->authorizeSuperAdmin();
        $this->resetValidation();
        $this->taxRule = $taxRule;

        if ($taxRule && $taxRule->exists) {
            $this->tax_type = $taxRule->tax_type;
            $this->name = $taxRule->name;
            $this->subsidiary_id = $taxRule->subsidiary_id !== null ? (string) $taxRule->subsidiary_id : '';
            $this->transaction_type = (string) ($taxRule->transaction_type ?? '');
            $this->funding_source = (string) ($taxRule->funding_source ?? '');
            $this->rate = $taxRule->rate !== null ? (string) (float) $taxRule->rate : '';
            $this->legal_basis = (string) ($taxRule->legal_basis ?? '');
            $this->effective_from = $taxRule->effective_from?->toDateString() ?? '';
            $this->effective_to = $taxRule->effective_to?->toDateString() ?? '';
            $this->change_notes = (string) ($taxRule->change_notes ?? '');
            $this->is_active = $taxRule->status === TaxRule::STATUS_ACTIVE;
        } else {
            $this->reset([
                'tax_type',
                'name',
                'subsidiary_id',
                'transaction_type',
                'funding_source',
                'rate',
                'legal_basis',
                'effective_from',
                'effective_to',
                'change_notes',
                'is_active',
            ]);
        }

        $this->modal = true;
    }

    public function save(): void
    {
        $this->authorizeSuperAdmin();

        $categoryNames = PackageCategory::query()->pluck('name')->all();
        $fundingNames = FundingSource::query()->pluck('name')->all();

        $this->validate([
            'tax_type' => ['required', Rule::in([TaxRule::TAX_PPN, TaxRule::TAX_PPH22, TaxRule::TAX_PPH23])],
            'name' => ['required', 'string', 'max:255'],
            'transaction_type' => ['nullable', 'string', 'max:50', Rule::in(array_merge([''], $categoryNames))],
            'funding_source' => ['nullable', 'string', 'max:50', Rule::in(array_merge([''], $fundingNames))],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'legal_basis' => ['required', 'string', 'min:5', 'max:5000'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'change_notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => 'boolean',
        ]);

        if ($this->subsidiary_id !== '') {
            $this->validate([
                'subsidiary_id' => ['numeric', 'integer', Rule::exists('subsidiaries', 'id')],
            ]);
        }

        $data = [
            'tax_type' => $this->tax_type,
            'name' => trim($this->name),
            'status' => $this->is_active ? TaxRule::STATUS_ACTIVE : TaxRule::STATUS_INACTIVE,
            'subsidiary_id' => $this->subsidiary_id === '' ? null : (int) $this->subsidiary_id,
            'transaction_type' => $this->transaction_type === '' ? null : $this->transaction_type,
            'funding_source' => $this->funding_source === '' ? null : $this->funding_source,
            'rate' => $this->rate,
            'legal_basis' => trim($this->legal_basis),
            'effective_from' => $this->effective_from !== '' ? $this->effective_from : null,
            'effective_to' => $this->effective_to !== '' ? $this->effective_to : null,
            'change_notes' => $this->change_notes !== '' ? trim($this->change_notes) : null,
        ];

        if ($this->is_active && $this->hasOverlappingActiveRule($data, $this->taxRule)) {
            $this->addError('tax_type', 'Aturan pajak aktif dengan konteks yang sama sudah tersedia untuk tipe pajak dan Anak Perusahaan ini.');

            return;
        }

        if ($this->taxRule?->exists) {
            if ($this->is_active && $this->taxRule->status !== TaxRule::STATUS_ACTIVE) {
                $data = array_merge($data, $this->activationStamp());
            }

            $this->taxRule->update($data);
            $this->success('Aturan pajak berhasil diperbarui.');
        } else {
            $data['created_by'] = auth()->id();
            $data['dpp_method'] = 'subtotal';
            $data['calculation_method'] = 'percentage';

            if ($this->is_active) {
                $data = array_merge($data, $this->activationStamp());
            }

            TaxRule::create($data);
            $this->success($this->is_active
                ? 'Aturan pajak baru berhasil dibuat dan aktif.'
                : 'Aturan pajak baru berhasil dibuat sebagai nonaktif.');
        }

        $this->modal = false;
        $this->reset([
            'tax_type',
            'name',
            'subsidiary_id',
            'transaction_type',
            'funding_source',
            'rate',
            'legal_basis',
            'effective_from',
            'effective_to',
            'change_notes',
            'is_active',
        ]);
    }

    public function activate(TaxRule $taxRule): void
    {
        $this->authorizeSuperAdmin();

        try {
            $taxRule->activate((int) auth()->id());
            $this->success('Aturan pajak aktif dan siap dipakai evaluasi transaksi.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function deactivate(TaxRule $taxRule): void
    {
        $this->authorizeSuperAdmin();

        try {
            $taxRule->deactivate();
            $this->success('Aturan pajak dinonaktifkan dan tidak dipakai evaluasi transaksi.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function delete(TaxRule $taxRule): void
    {
        $this->authorizeSuperAdmin();

        $taxRule->delete();
        $this->success('Aturan pajak berhasil dihapus.');
    }

    public function render()
    {
        $this->authorizeSuperAdmin();

        $typeLabels = [
            TaxRule::TAX_PPN => 'PPN',
            TaxRule::TAX_PPH22 => 'PPh 22',
            TaxRule::TAX_PPH23 => 'PPh 23',
        ];

        $taxRules = TaxRule::query()
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('legal_basis', 'like', '%'.$this->search.'%')
                        ->orWhere('transaction_type', 'like', '%'.$this->search.'%')
                        ->orWhere('funding_source', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterType !== '', fn (Builder $query): Builder => $query->where('tax_type', $this->filterType))
            ->when($this->filterStatus !== '', fn (Builder $query): Builder => $query->where('status', $this->filterStatus))
            ->when($this->filterSubsidiary === 'global', fn (Builder $query): Builder => $query->whereNull('subsidiary_id'))
            ->when(
                $this->filterSubsidiary !== '' && $this->filterSubsidiary !== 'global',
                fn (Builder $query): Builder => $query->where('subsidiary_id', (int) $this->filterSubsidiary)
            )
            ->with('subsidiary')
            ->orderByDesc('id')
            ->paginate(10);

        if ($taxRules->firstItem() !== null) {
            $taxRules->getCollection()->each(function (TaxRule $rule, int $index) use ($taxRules): void {
                $rule->setAttribute('row_no', $taxRules->firstItem() + $index);
            });
        }

        $userIds = $taxRules->getCollection()
            ->flatMap(fn (TaxRule $rule): array => [$rule->created_by, $rule->verified_by])
            ->filter()
            ->unique();
        $userNames = $userIds->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $userIds)->pluck('name', 'id');

        $headers = [
            ['key' => 'row_no', 'label' => 'No.', 'class' => 'w-14'],
            ['key' => 'tax_type', 'label' => 'Tipe'],
            ['key' => 'name', 'label' => 'Nama Aturan'],
            ['key' => 'entity', 'label' => 'Anak Perusahaan', 'class' => 'min-w-40'],
            ['key' => 'rate', 'label' => 'Tarif'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'min-w-48'],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'text-right w-16'],
        ];

        $categoryOptions = $this->optionsWithAll(
            PackageCategory::query()->orderBy('name')->pluck('name')->all(),
            $this->transaction_type
        );
        $fundingOptions = $this->optionsWithAll(
            FundingSource::query()->orderBy('name')->pluck('name')->all(),
            $this->funding_source
        );
        $subsidiaryOptions = array_merge(
            [['id' => '', 'name' => 'Semua (lintas Anak Perusahaan)']],
            Subsidiary::query()->active()->orderBy('name')->get()->map(fn (Subsidiary $subsidiary): array => [
                'id' => $subsidiary->id,
                'name' => $subsidiary->name.' · NPWP '.$subsidiary->npwp,
            ])->all()
        );

        if ($this->subsidiary_id !== ''
            && ! in_array((int) $this->subsidiary_id, array_column($subsidiaryOptions, 'id'), true)) {
            $missingSubsidiary = Subsidiary::query()->find((int) $this->subsidiary_id);
            if ($missingSubsidiary !== null) {
                $subsidiaryOptions[] = [
                    'id' => $missingSubsidiary->id,
                    'name' => $missingSubsidiary->name.' (nonaktif)',
                ];
            }
        }

        $filterSubsidiaryOptions = array_merge(
            [
                ['id' => '', 'name' => 'Semua entitas'],
                ['id' => 'global', 'name' => 'Lintas Anak Perusahaan'],
            ],
            Subsidiary::query()->active()->orderBy('name')->get()->map(fn (Subsidiary $subsidiary): array => [
                'id' => $subsidiary->id,
                'name' => $subsidiary->name,
            ])->all()
        );

        return view('livewire.master.tax-rule-index', [
            'taxRules' => $taxRules,
            'headers' => $headers,
            'typeLabels' => $typeLabels,
            'typeClasses' => [
                TaxRule::TAX_PPN => 'bg-indigo-100 text-indigo-700',
                TaxRule::TAX_PPH22 => 'bg-cyan-100 text-cyan-700',
                TaxRule::TAX_PPH23 => 'bg-purple-100 text-purple-700',
            ],
            'statusLabels' => [
                TaxRule::STATUS_ACTIVE => 'Aktif',
                TaxRule::STATUS_INACTIVE => 'Nonaktif',
            ],
            'statusClasses' => [
                TaxRule::STATUS_ACTIVE => 'bg-green-100 text-green-700',
                TaxRule::STATUS_INACTIVE => 'bg-red-100 text-red-600',
            ],
            'userNames' => $userNames,
            'coverageRows' => $this->coverageRows($typeLabels),
            'categoryOptions' => $categoryOptions,
            'fundingOptions' => $fundingOptions,
            'subsidiaryOptions' => $subsidiaryOptions,
            'filterSubsidiaryOptions' => $filterSubsidiaryOptions,
        ])->layout('layouts.app');
    }

    /**
     * @return array<int, array{label: string, cells: array<string, array{state: string, label: string}>}>
     */
    private function coverageRows(array $typeLabels): array
    {
        $activeRules = TaxRule::query()->active()->get();

        $rows = [[
            'label' => 'Lintas Anak Perusahaan',
            'cells' => $this->coverageCells(null, $typeLabels, $activeRules),
        ]];

        foreach (Subsidiary::query()->active()->orderBy('name')->get() as $subsidiary) {
            $rows[] = [
                'label' => $subsidiary->name,
                'cells' => $this->coverageCells((int) $subsidiary->id, $typeLabels, $activeRules),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, TaxRule>  $activeRules
     * @return array<string, array{state: string, label: string}>
     */
    private function coverageCells(?int $subsidiaryId, array $typeLabels, Collection $activeRules): array
    {
        $cells = [];

        foreach ($typeLabels as $type => $label) {
            $matches = $activeRules->filter(fn (TaxRule $rule): bool => $rule->tax_type === $type
                && ($rule->subsidiary_id === null || (int) $rule->subsidiary_id === $subsidiaryId));

            $count = $matches->count();

            if ($count === 0) {
                $cells[$type] = ['state' => 'missing', 'label' => 'Belum ada aturan aktif'];

                continue;
            }

            if ($count > 1) {
                $cells[$type] = ['state' => 'conflict', 'label' => 'Konflik ('.$count.' aturan)'];

                continue;
            }

            $rule = $matches->first();
            $eligible = $rule->approved_by !== null
                && $rule->approved_at !== null
                && $rule->verified_by !== null
                && $rule->verified_at !== null
                && trim((string) $rule->legal_basis) !== ''
                && $rule->rate !== null
                && (float) $rule->rate >= 0
                && (float) $rule->rate <= 100
                && $rule->calculation_method === 'percentage';

            $cells[$type] = $eligible
                ? ['state' => 'ok', 'label' => 'Aktif · '.(float) $rule->rate.'%']
                : ['state' => 'invalid', 'label' => 'Belum valid'];
        }

        return $cells;
    }

    /**
     * @return array<string, int|string>
     */
    private function activationStamp(): array
    {
        return [
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ];
    }

    /**
     * Cek apakah aturan aktif lain sudah tumpang tindih dengan konteks kandidat.
     * Nilai null atau 'all' pada sisi aturan diperlakukan sebagai wildcard,
     * sehingga aturan umum bersinggungan dengan aturan spesifik dianggap tumpang tindih.
     *
     * @param  array<string, mixed>  $data
     */
    private function hasOverlappingActiveRule(array $data, ?TaxRule $exclude): bool
    {
        $candidateContext = [
            'buyer_type' => null,
            'pkp_status' => null,
            'transaction_type' => $data['transaction_type'] ?? null,
            'item_type' => null,
            'funding_source' => $data['funding_source'] ?? null,
            'subsidiary_id' => ($data['subsidiary_id'] ?? '') === '' ? null : (int) $data['subsidiary_id'],
        ];

        $candidate = new TaxRule(array_merge([
            'tax_type' => $data['tax_type'],
        ], $candidateContext));

        foreach (TaxRule::query()->active()->forTaxType($data['tax_type'])->get() as $existing) {
            if ($exclude?->exists && $existing->id === $exclude->id) {
                continue;
            }

            $existingContext = [
                'buyer_type' => $existing->buyer_type,
                'pkp_status' => $existing->pkp_status,
                'transaction_type' => $existing->transaction_type,
                'item_type' => $existing->item_type,
                'funding_source' => $existing->funding_source,
                'subsidiary_id' => $existing->subsidiary_id,
            ];

            if ($existing->matches($candidateContext) || $candidate->matches($existingContext)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, array{id: string, name: string}>
     */
    private function optionsWithAll(array $names, string $current): array
    {
        $options = [['id' => '', 'name' => 'Semua']];

        foreach (array_unique($names) as $name) {
            $options[] = ['id' => $name, 'name' => $name];
        }

        if ($current !== '' && ! in_array($current, $names, true)) {
            $options[] = ['id' => $current, 'name' => $current.' (tidak ada di master)'];
        }

        return $options;
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}
