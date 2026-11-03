<?php

namespace App\Livewire\Master;

use App\Models\FundingSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class FundingSourceIndex extends Component
{
    use WithPagination, Toast;

    public bool $modal = false;
    public ?FundingSource $fundingSource = null;

    public string $search = '';

    public string $filterStatus = '';

    public string $nama_sumber_dana = '';

    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorizeSuperAdmin();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function openModal(?FundingSource $fundingSource = null): void
    {
        $this->authorizeSuperAdmin();
        $this->resetValidation();
        $this->fundingSource = $fundingSource;

        if ($fundingSource && $fundingSource->exists) {
            $this->nama_sumber_dana = $fundingSource->name;
            $this->is_active = $fundingSource->is_active;
        } else {
            $this->reset(['nama_sumber_dana', 'is_active']);
        }

        $this->modal = true;
    }

    public function save(): void
    {
        $this->authorizeSuperAdmin();

        $this->validate([
            'nama_sumber_dana' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('funding_sources', 'name')->ignore($this->fundingSource?->id),
            ],
            'is_active' => 'boolean',
        ]);

        $data = [
            'name' => $this->nama_sumber_dana,
            'is_active' => $this->is_active,
        ];

        if ($this->fundingSource?->exists) {
            $this->fundingSource->update($data);
            $this->success('Sumber dana berhasil diperbarui.');
        } else {
            FundingSource::create($data);
            $this->success('Sumber dana baru berhasil ditambahkan.');
        }

        $this->modal = false;
        $this->reset(['nama_sumber_dana', 'is_active']);
    }

    public function toggleActive(FundingSource $fundingSource): void
    {
        $this->authorizeSuperAdmin();
        $fundingSource->isActive() ? $fundingSource->deactivate() : $fundingSource->activate();
        $this->success('Status sumber dana diperbarui.');
    }

    public function delete(FundingSource $fundingSource): void
    {
        $this->authorizeSuperAdmin();

        if ($fundingSource->procurementRequests()->exists()) {
            $this->error('Sumber dana tidak dapat dihapus karena sudah digunakan dalam transaksi.');

            return;
        }

        $fundingSource->delete();
        $this->success('Sumber dana berhasil dihapus.');
    }

    public function render()
    {
        $this->authorizeSuperAdmin();

        $baseQuery = FundingSource::query();
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
        ];

        $sources = (clone $baseQuery)
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus === 'active', fn(Builder $query) => $query->where('is_active', true))
            ->when($this->filterStatus === 'inactive', fn(Builder $query) => $query->where('is_active', false))
            ->latest()
            ->paginate(10);

        if ($sources->firstItem() !== null) {
            $sources->getCollection()->each(function (FundingSource $source, int $index) use ($sources) {
                $source->setAttribute('row_no', $sources->firstItem() + $index);
            });
        }

        $headers = [
            ['key' => 'row_no', 'label' => 'No.', 'class' => 'w-14'],
            ['key' => 'name', 'label' => 'Nama Sumber Dana'],
            ['key' => 'is_active', 'label' => 'Status'],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'text-right w-16'],
        ];

        return view('livewire.master.funding-source-index', [
            'sources' => $sources,
            'headers' => $headers,
            'stats' => $stats,
        ])->layout('layouts.app');
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}