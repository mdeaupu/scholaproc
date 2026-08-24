<?php

namespace App\Livewire\Master;

use App\Models\FundingSource;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class FundingSourceIndex extends Component
{
    use WithPagination, Toast;

    public bool $modal = false;
    public ?FundingSource $fundingSource = null;

    public string $name = '';

    public function mount()
    {
        abort_unless(auth()->user()->role === 'superadmin' || auth()->user()->role === 'admin', 403);
    }

    public function openModal(?FundingSource $fundingSource = null)
    {
        $this->resetValidation();
        $this->fundingSource = $fundingSource;

        if ($fundingSource && $fundingSource->exists) {
            $this->name = $fundingSource->name;
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
                'min:2',
                Rule::unique('funding_sources', 'name')->ignore($this->fundingSource?->id)
            ]
        ]);

        if ($this->fundingSource && $this->fundingSource->exists) {
            $this->fundingSource->update(['name' => $this->name]);
            $this->success('Sumber dana berhasil diperbarui.');
        } else {
            FundingSource::create([
                'name' => $this->name,
                'is_active' => true,
            ]);
            $this->success('Sumber dana baru berhasil ditambahkan.');
        }

        $this->modal = false;
    }

    public function toggleActive(FundingSource $fundingSource)
    {
        $fundingSource->isActive() ? $fundingSource->deactivate() : $fundingSource->activate();
        $this->success('Status sumber dana diperbarui.');
    }

    public function delete(FundingSource $fundingSource)
    {
        if ($fundingSource->procurementRequests()->exists()) {
            $this->error('Sumber dana tidak dapat dihapus karena sudah digunakan dalam transaksi.');
            return;
        }

        $fundingSource->delete();
        $this->success('Sumber dana berhasil dihapus.');
    }

    public function render()
    {
        $headers = [
            ['key' => 'id', 'label' => '#'],
            ['key' => 'name', 'label' => 'Nama Sumber Dana'],
            ['key' => 'is_active', 'label' => 'Status'],
            ['key' => 'actions', 'label' => 'Aksi', 'sortable' => false],
        ];

        return view('livewire.master.funding-source-index', [
            'sources' => FundingSource::latest()->paginate(10),
            'headers' => $headers
        ])->layout('layouts.app');
    }
}