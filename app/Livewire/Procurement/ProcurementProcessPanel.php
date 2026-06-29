<?php

namespace App\Livewire\Procurement;

use App\Models\ProcurementRequest;
use App\Models\Supplier;
use Exception;
use Livewire\Component;
use Mary\Traits\Toast;

class ProcurementProcessPanel extends Component
{
    use Toast;

    public ProcurementRequest $procurementRequest;

    public $rejectReason = '';
    public $supplierId = '';
    public $items;


    public bool $rejectModal = false;
    public bool $supplierModal = false;

    public function mount(ProcurementRequest $procurementRequest)
    {
        $procurementRequest->load(['items', 'histories.createdBy', 'school', 'supplier']);
        $this->procurementRequest = $procurementRequest;
        $this->items = $procurementRequest->items;

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

    public function submitRequest()
    {
        try {
            $this->procurementRequest->submit(auth()->user());
            $this->success('Pengajuan berhasil di-submit ke sistem.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function verify()
    {
        try {
            $this->procurementRequest->verify(auth()->user());
            $this->success('Pengajuan berhasil diverifikasi.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function reject()
    {
        $this->validate([
            'rejectReason' => 'required|string|min:5'
        ], [
            'rejectReason.required' => 'Alasan penolakan wajib diisi.'
        ]);

        try {
            $this->procurementRequest->reject(auth()->user(), $this->rejectReason);
            $this->success('Pengajuan telah ditolak.');
            $this->rejectModal = false;
            $this->rejectReason = '';
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function assignSupplier()
    {
        $this->validate([
            'supplierId' => 'required|exists:suppliers,id'
        ]);

        $supplier = Supplier::findOrFail($this->supplierId);

        try {
            $this->procurementRequest->assignSupplier($supplier, auth()->user());
            $this->success("Supplier {$supplier->company_name} berhasil ditunjuk.");
            $this->supplierModal = false;
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function markItemsPrepared()
    {
        try {
            $this->procurementRequest->markItemsPrepared(auth()->user());
            $this->success('Status pengajuan diubah menjadi: Barang/Jasa Disiapkan.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function complete()
    {
        try {
            $this->procurementRequest->complete(auth()->user());
            $this->success('Proses pengadaan telah selesai dan BAST dapat diterbitkan.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.procurement.procurement-process-panel', [
            'suppliers' => Supplier::all()
        ])->layout('layouts.app');
    }
}
