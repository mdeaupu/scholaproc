<?php

namespace App\Livewire\Subsidiary;

use App\Models\ProcurementRequest;
use App\Models\Subsidiary;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class SubsidiaryIndex extends Component
{
    use Toast, WithPagination;

    public string $search = '';

    public string $filterStatus = '';

    public bool $confirmingDestroy = false;

    public ?int $targetSubsidiaryId = null;

    public string $targetSubsidiaryName = '';

    public bool $detailModal = false;

    public ?Subsidiary $selectedSubsidiary = null;

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

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $subsidiaries = Subsidiary::with('legalDocuments')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('npwp', 'like', '%'.$this->search.'%')
                        ->orWhere('nib', 'like', '%'.$this->search.'%')
                        ->orWhere('address', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterStatus !== '', fn ($query) => $query->where('status', $this->filterStatus))
            ->latest()
            ->paginate(10);

        $firstItem = $subsidiaries->firstItem();
        if ($firstItem !== null) {
            $subsidiaries->getCollection()->each(function (Subsidiary $subsidiary, int $index) use ($firstItem) {
                $subsidiary->setAttribute('row_no', $firstItem + $index);
            });
        }

        return view('livewire.subsidiary.subsidiary-index', [
            'subsidiaries' => $subsidiaries,
            'totalSubsidiaries' => Subsidiary::count(),
            'activeSubsidiaries' => Subsidiary::active()->count(),
            'inactiveSubsidiaries' => Subsidiary::where('status', 'inactive')->count(),
            'assignedSubsidiaries' => Subsidiary::whereHas('procurementRequests')->count(),
            'totalProjects' => ProcurementRequest::whereNotNull('subsidiary_id')->count(),
            'headers' => [
                ['key' => 'no', 'label' => 'No.', 'class' => 'w-12 text-center hidden md:table-cell', 'sortable' => false],
                ['key' => 'name', 'label' => 'Nama Anak Perusahaan'],
                ['key' => 'npwp', 'label' => 'NPWP', 'class' => 'w-44', 'sortable' => false],
                ['key' => 'director_name', 'label' => 'Direktur', 'class' => 'hidden lg:table-cell'],
                ['key' => 'status', 'label' => 'Status', 'class' => 'w-28', 'sortable' => false],
                ['key' => 'actions', 'label' => 'Aksi', 'class' => 'w-14 text-right', 'sortable' => false],
            ],
        ])->layout('layouts.app');
    }

    public function show(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->selectedSubsidiary = Subsidiary::with([
            'legalDocuments',
            'procurementRequests.school',
            'procurementRequests.packageCategory',
        ])->findOrFail($id);

        $this->detailModal = true;
    }

    public function confirmDestroy(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $subsidiary = Subsidiary::findOrFail($id);
        if (! $subsidiary->canBeDeleted()) {
            $this->error('Anak Perusahaan yang memiliki transaksi atau dokumen tidak dapat dihapus.');

            return;
        }

        $this->targetSubsidiaryId = $subsidiary->id;
        $this->targetSubsidiaryName = $subsidiary->name;
        $this->confirmingDestroy = true;
    }

    public function destroy(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if (! $this->targetSubsidiaryId) {
            return;
        }

        $subsidiary = Subsidiary::findOrFail($this->targetSubsidiaryId);
        if (! $subsidiary->canBeDeleted()) {
            $this->confirmingDestroy = false;
            $this->error('Anak Perusahaan yang memiliki transaksi atau dokumen tidak dapat dihapus.');

            return;
        }

        DB::transaction(fn () => $subsidiary->delete());

        $this->confirmingDestroy = false;
        $this->targetSubsidiaryId = null;
        $this->targetSubsidiaryName = '';
        $this->success("Anak Perusahaan {$subsidiary->name} berhasil dihapus.");
    }
}
