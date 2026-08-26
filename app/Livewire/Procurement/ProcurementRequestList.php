<?php

namespace App\Livewire\Procurement;

use App\Models\ProcurementRequest;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class ProcurementRequestList extends Component
{
    use WithPagination, Toast;

    public $search = '';
    public string $filterStatus = '';

    public array $headers = [
        ['key' => 'school.name', 'label' => 'Sekolah'],
        ['key' => 'packageCategory.name', 'label' => 'Kategori Paket'],
        ['key' => 'items_count', 'label' => 'Jumlah Jenis'],
        ['key' => 'total_budget', 'label' => 'Total Anggaran'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'action', 'label' => 'Aksi'],
    ];

    public function mount()
    {
        if (session()->has('toast_success')) {
            $this->success(
                title: 'Berhasil!',
                description: session('toast_success'),
                position: 'toast-top toast-end',
                icon: 'o-check-circle',
                css: 'alert-success'
            );
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        $user = auth()->user();

        if (!$user->isSchool()) {
            $this->error('Hanya pengguna sekolah yang dapat menghapus draft pengajuan.');
            return;
        }

        $procurement = ProcurementRequest::bySchool($user->school_id)
            ->where('status', 'draft')
            ->findOrFail($id);

        $procurement->items()->delete();
        $procurement->delete();

        $this->success('Draft pengajuan pengadaan berhasil dihapus.');
    }

    public function render()
    {
        $requests = ProcurementRequest::with(['school', 'packageCategory'])
            ->withCount('items')
            ->withSum('items as estimated_subtotal', DB::raw('quantity * estimated_price'))
            ->when(auth()->user()->can('admin-school-only'), function ($query) {
                return $query->bySchool(auth()->user()->school_id);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('packageCategory', function ($q2) {
                        $q2->where('name', 'like', '%' . $this->search . '%');
                    })->orWhereHas('school', function ($q2) {
                        $q2->where('name', 'like', '%' . $this->search . '%');
                    });
                });
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->latest()
            ->paginate(10);

        return view('livewire.procurement.procurement-request-list', [
            'requests' => $requests
        ])->layout('layouts.app');
    }
}
