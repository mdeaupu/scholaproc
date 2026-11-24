<?php

namespace App\Livewire\Master;

use App\Models\ItemUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class ItemUnitIndex extends Component
{
    use WithPagination, Toast;

    public bool $modal = false;
    public ?ItemUnit $itemUnit = null;

    public string $search = '';

    public string $filterStatus = '';

    public string $nama_satuan = '';

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

    public function openModal(?ItemUnit $itemUnit = null): void
    {
        $this->authorizeSuperAdmin();
        $this->resetValidation();
        $this->itemUnit = $itemUnit;

        if ($itemUnit && $itemUnit->exists) {
            $this->nama_satuan = $itemUnit->name;
            $this->is_active = $itemUnit->is_active;
        } else {
            $this->reset(['nama_satuan', 'is_active']);
        }

        $this->modal = true;
    }

    public function save(): void
    {
        $this->authorizeSuperAdmin();

        $this->validate([
            'nama_satuan' => [
                'required',
                'string',
                'max:50',
                Rule::unique('item_units', 'name')->ignore($this->itemUnit?->id),
            ],
            'is_active' => 'boolean',
        ]);

        $data = [
            'name' => $this->nama_satuan,
            'is_active' => $this->is_active,
        ];

        if ($this->itemUnit?->exists) {
            $this->itemUnit->update($data);
            $this->success('Satuan berhasil diperbarui.');
        } else {
            ItemUnit::create($data);
            $this->success('Satuan baru berhasil ditambahkan.');
        }

        $this->modal = false;
        $this->reset(['nama_satuan', 'is_active']);
    }

    public function toggleActive(ItemUnit $itemUnit): void
    {
        $this->authorizeSuperAdmin();
        $itemUnit->isActive() ? $itemUnit->deactivate() : $itemUnit->activate();
        $this->success('Status satuan diperbarui.');
    }

    public function delete(ItemUnit $itemUnit): void
    {
        $this->authorizeSuperAdmin();

        if ($itemUnit->procurementRequestItems()->exists()) {
            $this->error('Satuan tidak dapat dihapus karena sedang dipakai pada detail barang pengadaan.');

            return;
        }

        $itemUnit->delete();
        $this->success('Satuan berhasil dihapus.');
    }

    public function render()
    {
        $this->authorizeSuperAdmin();

        $baseQuery = ItemUnit::query();
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
        ];

        $units = (clone $baseQuery)
            ->when($this->search, function (Builder $query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterStatus === 'active', fn(Builder $query) => $query->where('is_active', true))
            ->when($this->filterStatus === 'inactive', fn(Builder $query) => $query->where('is_active', false))
            ->latest()
            ->paginate(10);

        if ($units->firstItem() !== null) {
            $units->getCollection()->each(function (ItemUnit $unit, int $index) use ($units) {
                $unit->setAttribute('row_no', $units->firstItem() + $index);
            });
        }

        $headers = [
            ['key' => 'row_no', 'label' => 'No.', 'class' => 'w-14'],
            ['key' => 'name', 'label' => 'Nama Satuan'],
            ['key' => 'is_active', 'label' => 'Status'],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'text-right w-16'],
        ];

        return view('livewire.master.item-unit-index', [
            'units' => $units,
            'headers' => $headers,
            'stats' => $stats,
        ])->layout('layouts.app');
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}