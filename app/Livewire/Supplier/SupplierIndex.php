<?php

namespace App\Livewire\Supplier;

use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class SupplierIndex extends Component
{
    use WithPagination, Toast;

    public bool $confirmingDestroy = false;
    public ?int $targetSupplierId = null;
    public string $targetSupplierName = '';
    public string $search = '';
    public bool $detailModal = false;
    public ?Supplier $selectedSupplier = null;
    public array $supplierStats = [];

    public function mount(): void
    {
        if (session()->has('toast_success')) {
            $this->success(session('toast_success'));
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isAdminCv()) {
            abort(403, 'Akses ditolak. Hanya Owner dan Admin CV yang dapat melihat halaman ini.');
        }

        $suppliers = Supplier::with('legalDocuments')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('company_name', 'like', '%' . $this->search . '%')
                        ->orWhere('pic_name', 'like', '%' . $this->search . '%')
                        ->orWhere('npwp', 'like', '%' . $this->search . '%')
                        ->orWhere('nib', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(10);

        $headers = [
            ['key' => 'company_name', 'label' => 'Nama Perusahaan'],
            ['key' => 'pic_name', 'label' => 'Nama PIC'],
            ['key' => 'phone', 'label' => 'No. Telepon', 'sortable' => false],
            ['key' => 'npwp', 'label' => 'NPWP', 'class' => 'font-mono'],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'text-right w-48', 'sortable' => false]
        ];

        return view('livewire.supplier.supplier-index', [
            'suppliers' => $suppliers,
            'headers' => $headers
        ])->layout('layouts.app');
    }

    public function show(int $id): void
    {
        $this->selectedSupplier = Supplier::with(['legalDocuments', 'procurementRequests'])->findOrFail($id);

        $this->supplierStats = [
            'total_projects' => $this->selectedSupplier->totalProjects(),
            'total_value' => $this->selectedSupplier->totalProjectValue(),
            'is_complete' => $this->selectedSupplier->hasCompleteLegalDocuments(),
            'active_permit' => $this->selectedSupplier->activeBusinessPermit(),
        ];

        $this->detailModal = true;
    }

    public function confirmDestroy(int $id, string $name): void
    {
        $this->targetSupplierId = $id;
        $this->targetSupplierName = $name;
        $this->confirmingDestroy = true;
    }

    public function destroy(): void
    {
        if ($this->targetSupplierId) {
            $supplier = Supplier::findOrFail($this->targetSupplierId);
            $supplier->delete();

            $this->error("Supplier {$supplier->company_name} berhasil dihapus dari sistem.");

            $this->confirmingDestroy = false;
            $this->targetSupplierId = null;
            $this->targetSupplierName = '';
        }
    }
}
