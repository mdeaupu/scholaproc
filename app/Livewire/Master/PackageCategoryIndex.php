<?php

namespace App\Livewire\Master;

use App\Models\PackageCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class PackageCategoryIndex extends Component
{
    use WithPagination, Toast;

    public bool $modal = false;
    public ?PackageCategory $category = null;

    public string $search = '';

    public string $filterStatus = '';

    public string $nama_kategori = '';

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

    public function openModal(?PackageCategory $category = null): void
    {
        $this->authorizeSuperAdmin();
        $this->resetValidation();
        $this->category = $category;

        if ($category && $category->exists) {
            $this->nama_kategori = $category->name;
            $this->is_active = $category->is_active;
        } else {
            $this->reset(['nama_kategori', 'is_active']);
        }

        $this->modal = true;
    }

    public function save(): void
    {
        $this->authorizeSuperAdmin();

        $this->validate([
            'nama_kategori' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('package_categories', 'name')->ignore($this->category?->id),
            ],
            'is_active' => 'boolean',
        ]);

        $data = [
            'name' => $this->nama_kategori,
            'is_active' => $this->is_active,
        ];

        if ($this->category?->exists) {
            $this->category->update($data);
            $this->success('Kategori berhasil diperbarui.');
        } else {
            PackageCategory::create($data);
            $this->success('Kategori paket berhasil ditambahkan.');
        }

        $this->modal = false;
        $this->reset(['nama_kategori', 'is_active']);
    }

    public function toggleActive(PackageCategory $category): void
    {
        $this->authorizeSuperAdmin();
        $category->isActive() ? $category->deactivate() : $category->activate();
        $this->success('Status kategori diperbarui.');
    }

    public function delete(PackageCategory $category): void
    {
        $this->authorizeSuperAdmin();

        if ($category->procurementRequests()->exists()) {
            $this->error('Kategori tidak dapat dihapus karena sedang digunakan.');

            return;
        }

        $category->delete();
        $this->success('Kategori berhasil dihapus.');
    }

    public function render()
    {
        $this->authorizeSuperAdmin();

        $baseQuery = PackageCategory::query();
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
        ];

        $categories = (clone $baseQuery)
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus === 'active', fn(Builder $query) => $query->where('is_active', true))
            ->when($this->filterStatus === 'inactive', fn(Builder $query) => $query->where('is_active', false))
            ->latest()
            ->paginate(10);

        if ($categories->firstItem() !== null) {
            $categories->getCollection()->each(function (PackageCategory $category, int $index) use ($categories) {
                $category->setAttribute('row_no', $categories->firstItem() + $index);
            });
        }

        $headers = [
            ['key' => 'row_no', 'label' => 'No.', 'class' => 'w-14'],
            ['key' => 'name', 'label' => 'Nama Kategori'],
            ['key' => 'is_active', 'label' => 'Status'],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'text-right w-16'],
        ];

        return view('livewire.master.package-category-index', [
            'categories' => $categories,
            'headers' => $headers,
            'stats' => $stats,
        ])->layout('layouts.app');
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}