<?php

namespace App\Livewire\Master;

use App\Models\PackageCategory;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class PackageCategoryIndex extends Component
{
    use WithPagination, Toast;

    public bool $modal = false;
    public ?PackageCategory $category = null;

    public string $name = '';

    public function mount()
    {
        abort_unless(auth()->user()->role === 'superadmin' || auth()->user()->role === 'admin', 403);
    }

    public function openModal(?PackageCategory $category = null)
    {
        $this->resetValidation();
        $this->category = $category;

        if ($category && $category->exists) {
            $this->name = $category->name;
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
                'min:3',
                Rule::unique('package_categories', 'name')->ignore($this->category?->id)
            ]
        ]);

        if ($this->category && $this->category->exists) {
            $this->category->update(['name' => $this->name]);
            $this->success('Kategori berhasil diperbarui.');
        } else {
            PackageCategory::create([
                'name' => $this->name,
                'is_active' => true,
            ]);
            $this->success('Kategori baru berhasil ditambahkan.');
        }

        $this->modal = false;
    }

    public function toggleActive(PackageCategory $category)
    {
        $category->isActive() ? $category->deactivate() : $category->activate();
        $this->success('Status kategori diperbarui.');
    }

    public function delete(PackageCategory $category)
    {
        if ($category->procurementRequests()->exists()) {
            $this->error('Kategori tidak dapat dihapus karena sedang digunakan.');
            return;
        }

        $category->delete();
        $this->success('Kategori berhasil dihapus.');
    }

    public function render()
    {
        $headers = [
            ['key' => 'id', 'label' => '#'],
            ['key' => 'name', 'label' => 'Nama Kategori'],
            ['key' => 'is_active', 'label' => 'Status'],
            ['key' => 'actions', 'label' => 'Aksi', 'sortable' => false],
        ];

        return view('livewire.master.package-category-index', [
            'categories' => PackageCategory::latest()->paginate(10),
            'headers' => $headers
        ])->layout('layouts.app');
    }
}