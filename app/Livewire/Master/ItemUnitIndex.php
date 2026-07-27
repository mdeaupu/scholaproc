<?php

namespace App\Livewire\Master;

use App\Models\ItemUnit;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class ItemUnitIndex extends Component
{
    use WithPagination, Toast;

    public bool $modal = false;
    public ?ItemUnit $itemUnit = null;

    public string $name = '';

    public function mount()
    {
        abort_unless(auth()->user()->role === 'superadmin' || auth()->user()->role === 'admin', 403);
    }

    public function openModal(?ItemUnit $itemUnit = null)
    {
        $this->resetValidation();
        $this->itemUnit = $itemUnit;

        if ($itemUnit && $itemUnit->exists) {
            $this->name = $itemUnit->name;
        } else {
            $this->reset('name');
        }

        $this->modal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => [
                'required',
                'max:50',
                Rule::unique('item_units', 'name')->ignore($this->itemUnit?->id)
            ]
        ]);

        if ($this->itemUnit && $this->itemUnit->exists) {
            $this->itemUnit->update(['name' => $this->name]);
            $this->success('Satuan berhasil diperbarui.');
        } else {
            ItemUnit::create([
                'name' => $this->name,
                'is_active' => true,
            ]);
            $this->success('Satuan baru berhasil ditambahkan.');
        }

        $this->modal = false;
    }

    public function toggleActive(ItemUnit $itemUnit)
    {
        $itemUnit->isActive() ? $itemUnit->deactivate() : $itemUnit->activate();
        $this->success('Status satuan diperbarui.');
    }

    public function delete(ItemUnit $itemUnit)
    {
        if ($itemUnit->procurementRequestItems()->exists()) {
            $this->error('Satuan tidak dapat dihapus karena sedang dipakai pada detail barang pengadaan.');
            return;
        }

        $itemUnit->delete();
        $this->success('Satuan berhasil dihapus.');
    }

    public function render()
    {
        $headers = [
            ['key' => 'id', 'label' => '#'],
            ['key' => 'name', 'label' => 'Nama Satuan'],
            ['key' => 'is_active', 'label' => 'Status'],
            ['key' => 'actions', 'label' => 'Aksi', 'sortable' => false],
        ];

        return view('livewire.master.item-unit-index', [
            'units' => ItemUnit::latest()->paginate(10),
            'headers' => $headers
        ])->layout('layouts.app');
    }
}