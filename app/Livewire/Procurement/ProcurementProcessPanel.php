<?php

namespace App\Livewire\Procurement;

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\Supplier;
use Exception;
use Illuminate\Support\Facades\DB;
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
    public bool $taxModal = false;
    public bool $signatoryModal = false;

    public bool $isTaxable = false;
    public $ppnRate = 11;
    public $pph22Rate = 0;
    public $pph23Rate = 0;

    public bool $priceModal = false;
    public array $inputPrices = [];

    public array $signatoriesData = [
        'headmaster' => ['name' => '', 'nip' => '', 'title' => 'Kepala Sekolah'],
        'inspector' => ['name' => '', 'nip' => '', 'title' => 'Pemeriksa Barang'],
        'treasurer' => ['name' => '', 'nip' => '', 'title' => 'Bendahara BOS'],
    ];

    public function mount(ProcurementRequest $procurementRequest)
    {
        $procurementRequest->load(['items', 'histories.createdBy', 'school', 'supplier']);
        $this->procurementRequest = $procurementRequest;
        $this->items = $procurementRequest->items;

        $this->isTaxable = (bool) $procurementRequest->is_taxable;
        $this->ppnRate = $procurementRequest->ppn_rate ?? 11;
        $this->pph22Rate = $procurementRequest->pph_22_rate ?? 0;
        $this->pph23Rate = $procurementRequest->pph_23_rate ?? 0;

        foreach ($procurementRequest->signatories as $sig) {
            if (isset($this->signatoriesData[$sig->role])) {
                $this->signatoriesData[$sig->role] = [
                    'name' => $sig->name,
                    'nip' => $sig->nip,
                    'title' => $sig->title,
                ];
            }
        }

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

    private function authorizeAdminSchool(): void
    {
        abort_if(!auth()->user()->isAdminSchool(), 403, 'Akses Ditolak: Hanya Admin Sekolah yang berhak melakukan tindakan ini.');
    }

    private function authorizeAdminCvOrOwner(): void
    {
        $user = auth()->user();
        abort_if(!$user->isOwner() && !$user->isAdminCv(), 403, 'Akses Ditolak: Hanya Pihak CV yang berhak memproses administrasi.');
    }

    public function submitRequest()
    {
        $this->authorizeAdminSchool();

        try {
            $this->procurementRequest->submit(auth()->user());
            $this->success('Pengajuan berhasil di-submit ke sistem.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function verify()
    {
        $this->authorizeAdminCvOrOwner();

        try {
            $this->procurementRequest->verify(auth()->user());
            $this->success('Pengajuan berhasil diverifikasi.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function reject()
    {
        $this->authorizeAdminCvOrOwner();

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
        $this->authorizeAdminCvOrOwner();

        $this->validate([
            'supplierId' => 'required|exists:suppliers,id'
        ]);

        $supplier = Supplier::findOrFail($this->supplierId);

        try {
            DB::transaction(function () use ($supplier) {
                $this->procurementRequest->assignSupplier($supplier, auth()->user());
                $this->procurementRequest->documents()->updateOrCreate(
                    ['document_type' => 'purchase_order'],
                    [
                        'document_number' => 'DRAFT-SPK/' . $this->procurementRequest->id . '/' . date('Y'),
                        'document_date' => now()->toDateString(),
                    ]
                );
            });

            $this->procurementRequest->load(['supplier', 'documents']);
            $this->procurementRequest->refresh();

            $this->success("Supplier {$supplier->company_name} ditunjuk dan Draft Surat Pesanan berhasil dibuat.");
            $this->supplierModal = false;
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function markItemsPrepared()
    {
        $this->authorizeAdminCvOrOwner();

        try {
            $this->procurementRequest->markItemsPrepared(auth()->user());
            $this->success('Status pengajuan diubah menjadi: Barang/Jasa Disiapkan.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function complete()
    {
        $this->authorizeAdminCvOrOwner();

        try {
            $this->procurementRequest->complete(auth()->user());
            $this->success('Proses pengadaan telah selesai dan BAST dapat diterbitkan.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function setTaxes()
    {
        $this->authorizeAdminCvOrOwner();

        $this->validate([
            'ppnRate' => 'numeric|min:0|max:100',
            'pph22Rate' => 'numeric|min:0|max:100',
            'pph23Rate' => 'numeric|min:0|max:100',
        ]);
        try {
            $this->procurementRequest->update([
                'is_taxable' => $this->isTaxable,
                'ppn_rate' => $this->isTaxable ? $this->ppnRate : 0,
                'pph_22_rate' => $this->isTaxable ? $this->pph22Rate : 0,
                'pph_23_rate' => $this->isTaxable ? $this->pph23Rate : 0,
            ]);

            $this->taxModal = false;
            $this->success('Konfigurasi komponen perpajakan berhasil diterapkan.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function setSignatories()
    {
        $this->authorizeAdminCvOrOwner();

        $this->validate([
            'signatoriesData.*.name' => 'required|string|min:3',
            'signatoriesData.*.title' => 'required|string',
        ], [
            'signatoriesData.*.name.required' => 'Nama wajib diisi.'
        ]);

        try {
            DB::transaction(function () {
                $this->procurementRequest->signatories()->delete();

                foreach ($this->signatoriesData as $role => $data) {
                    $this->procurementRequest->signatories()->create([
                        'role' => $role,
                        'name' => $data['name'],
                        'nip' => $data['nip'] ?: null,
                        'title' => $data['title'],
                    ]);
                }
            });

            $this->signatoryModal = false;
            $this->procurementRequest->load('signatories');
            $this->success('Susunan pejabat penandatangan berhasil disimpan.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function setDocumentNumbers()
    {
        $this->authorizeAdminCvOrOwner();

        try {
            $this->procurementRequest->generateOfficialDocuments();

            $this->procurementRequest->load('documents');
            $this->procurementRequest->refresh();

            $this->success('Penomoran dokumen dinas (SPK, BAST, dll) berhasil digenerate.');
        } catch (Exception $e) {
            $this->error('Gagal generate nomor: ' . $e->getMessage());
        }
    }

    public function openPriceModal()
    {
        $this->authorizeAdminCvOrOwner();

        $this->inputPrices = [];
        foreach ($this->procurementRequest->items as $item) {
            $this->inputPrices[$item->id] = $item->official_price ?? $item->estimated_price;
        }
        $this->priceModal = true;
    }

    public function saveOfficialPrices()
    {
        $this->authorizeAdminCvOrOwner();

        $this->validate([
            'inputPrices.*' => 'required|numeric|min:0',
        ], [
            'inputPrices.*.required' => 'Harga resmi wajib diisi.',
            'inputPrices.*.numeric' => 'Harga harus berupa angka.',
        ]);
        try {
            DB::transaction(function () {
                foreach ($this->inputPrices as $itemId => $price) {
                    ProcurementRequestItem::where('id', $itemId)
                        ->update(['official_price' => $price]);
                }
            });

            $this->priceModal = false;

            $this->procurementRequest->load('items');
            $this->procurementRequest->refresh();

            $this->success('Harga resmi kontrak berhasil disimpan dan divalidasi.');
        } catch (Exception $e) {
            $this->error('Gagal menyimpan harga: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.procurement.procurement-process-panel', [
            'suppliers' => Supplier::all()
        ])->layout('layouts.app');
    }
}
