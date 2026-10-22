<?php

namespace App\Livewire\Supplier;

use App\Enums\DocumentType;
use App\Models\ProcurementRequest;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class SupplierIndex extends Component
{
    use Toast, WithPagination;

    public bool $confirmingDestroy = false;

    public ?int $targetSupplierId = null;

    public string $targetSupplierName = '';

    public string $search = '';

    public string $filterLegalitas = '';

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

    public function updatingFilterLegalitas(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->isAdmin()) {
            abort(403, 'Akses ditolak. Hanya Owner dan Admin CV yang dapat melihat halaman ini.');
        }

        $suppliers = Supplier::with('legalDocuments')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('company_name', 'like', '%'.$this->search.'%')
                        ->orWhere('pic_name', 'like', '%'.$this->search.'%')
                        ->orWhere('npwp', 'like', '%'.$this->search.'%')
                        ->orWhere('nib', 'like', '%'.$this->search.'%')
                        ->orWhere('address', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterLegalitas === 'lengkap', function ($query) {
                $query->whereHas('legalDocuments', function ($q) {
                    $q->where('document_type', DocumentType::DEED);
                })->whereHas('legalDocuments', function ($q) {
                    $q->where('document_type', DocumentType::BUSINESS_PERMIT);
                });
            })
            ->when($this->filterLegalitas === 'belum', function ($query) {
                $query->where(function ($q) {
                    $q->whereDoesntHave('legalDocuments', function ($qd) {
                        $qd->where('document_type', DocumentType::DEED);
                    })->orWhereDoesntHave('legalDocuments', function ($qd) {
                        $qd->where('document_type', DocumentType::BUSINESS_PERMIT);
                    });
                });
            })
            ->latest()
            ->paginate(10);

        $firstItem = $suppliers->firstItem();
        if ($firstItem !== null) {
            $suppliers->getCollection()->each(function (Supplier $supplier, int $index) use ($firstItem) {
                $supplier->setAttribute('row_no', $firstItem + $index);
            });
        }

        $totalSuppliers = Supplier::count();
        $certifiedSuppliers = Supplier::whereHas('legalDocuments', function ($query) {
            $query->where('document_type', DocumentType::BUSINESS_PERMIT)
                ->where(function ($q) {
                    $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()->toDateString());
                });
        })->count();
        $completeLegalSuppliers = Supplier::whereHas('legalDocuments', function ($query) {
            $query->where('document_type', DocumentType::DEED);
        })->whereHas('legalDocuments', function ($query) {
            $query->where('document_type', DocumentType::BUSINESS_PERMIT);
        })->count();
        $suppliersWithProjects = Supplier::whereHas('procurementRequests')->count();

        $totalProjects = ProcurementRequest::whereNotNull('supplier_id')->count();
        $activeProjects = ProcurementRequest::whereNotNull('supplier_id')
            ->whereIn('status', ProcurementRequest::ACTIVE_STATUSES)
            ->count();
        $completedProjects = ProcurementRequest::whereNotNull('supplier_id')
            ->where('status', ProcurementRequest::STATUS_COMPLETED)
            ->count();

        $percentage = fn (int $count): float => $totalSuppliers > 0 ? round($count * 100 / $totalSuppliers, 1) : 0.0;

        $headers = [
            ['key' => 'no', 'label' => 'No.', 'class' => 'w-12 text-center hidden md:table-cell', 'sortable' => false],
            ['key' => 'company_name', 'label' => 'Nama Supplier'],
            ['key' => 'npwp', 'label' => 'NPWP', 'class' => 'w-44', 'sortable' => false],
            ['key' => 'address', 'label' => 'Alamat', 'class' => 'hidden lg:table-cell'],
            ['key' => 'legalitas', 'label' => 'Legalitas', 'class' => 'w-28', 'sortable' => false],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'w-14 text-right', 'sortable' => false],
        ];

        return view('livewire.supplier.supplier-index', [
            'suppliers' => $suppliers,
            'headers' => $headers,
            'totalSuppliers' => $totalSuppliers,
            'certifiedSuppliers' => $certifiedSuppliers,
            'completeLegalSuppliers' => $completeLegalSuppliers,
            'suppliersWithProjects' => $suppliersWithProjects,
            'percentCertified' => $percentage($certifiedSuppliers),
            'percentCompleteLegal' => $percentage($completeLegalSuppliers),
            'totalProjects' => $totalProjects,
            'activeProjects' => $activeProjects,
            'completedProjects' => $completedProjects,
        ])->layout('layouts.app');
    }

    public function show(int $id): void
    {
        $this->selectedSupplier = Supplier::with(['legalDocuments', 'procurementRequests.school', 'procurementRequests.packageCategory'])->findOrFail($id);

        $this->supplierStats = [
            'total_projects' => $this->selectedSupplier->totalProjects(),
            'total_value' => $this->selectedSupplier->totalProjectValue(),
            'is_complete' => $this->selectedSupplier->hasCompleteLegalDocuments(),
            'active_permit' => $this->selectedSupplier->activeBusinessPermit(),
            'active_projects' => $this->selectedSupplier->procurementRequests
                ->whereIn('status', ProcurementRequest::ACTIVE_STATUSES)->count(),
            'completed_projects' => $this->selectedSupplier->procurementRequests
                ->where('status', ProcurementRequest::STATUS_COMPLETED)->count(),
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

            $this->success("Supplier {$supplier->company_name} berhasil dihapus dari sistem.");

            $this->confirmingDestroy = false;
            $this->targetSupplierId = null;
            $this->targetSupplierName = '';
        }
    }
}
