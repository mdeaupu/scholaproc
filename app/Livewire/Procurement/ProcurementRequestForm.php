<?php

namespace App\Livewire\Procurement;

use App\Models\ProcurementRequest;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Mary\Traits\Toast;

class ProcurementRequestForm extends Component
{
    use Toast;

    public ?ProcurementRequest $procurement = null;
    public bool $isEdit = false;

    public $school_id = '';
    public $package_category = '';
    public $budget_year = '';
    public $funding_source = '';

    public $items = [];
    public $school_name = '';

    public $previousUrl = null;

    public function mount($id = null)
    {
        $user = auth()->user();

        $this->previousUrl = url()->previous() ?: route('procurement.index');

        if ($this->previousUrl === url()->current()) {
            $this->previousUrl = route('procurement.index');
        }

        if ($id) {
            $this->isEdit = true;
            $this->procurement = ProcurementRequest::findOrFail($id);

            if (!$user->can('admin-school-only')) {
                session()->flash('error', 'Anda tidak memiliki hak akses untuk mengubah pengajuan ini.');
                return redirect()->route('procurement.index');
            }

            if ($this->procurement->status !== ProcurementRequest::STATUS_DRAFT) {
                session()->flash('error', 'Pengajuan tidak dapat diedit karena sudah diproses.');
                return redirect()->route('procurement.index');
            }

            if ($this->procurement->school_id !== $user->school_id) {
                session()->flash('error', 'Anda tidak memiliki akses untuk mengedit pengajuan ini.');
                return redirect()->route('procurement.index');
            }

            $this->school_id = $this->procurement->school_id;
            $this->school_name = $this->procurement->school?->name;
            $this->package_category = $this->procurement->package_category;
            $this->budget_year = $this->procurement->budget_year;
            $this->funding_source = $this->procurement->funding_source;

            $this->items = $this->procurement->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'specification' => $item->specification ?? '',
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'estimated_price' => $item->estimated_price,
                ];
            })->toArray();
        } else {
            if ($user->can('admin-school-only')) {
                $this->school_id = $user->school_id;
                $this->school_name = $user->school?->name ?? 'Sekolah Anda';
            }

            $this->addItem();
        }
    }

    public function cancel()
    {
        return $this->redirect($this->previousUrl, navigate: true);
    }

    public function getEstimatedSubtotalProperty()
    {
        return collect($this->items)->sum(function ($item) {
            $price = (float) ($item['estimated_price'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);

            return $price * $quantity;
        });
    }

    public function addItem()
    {
        $this->items[] = [
            'item_name' => '',
            'specification' => '',
            'quantity' => 1,
            'unit' => '',
            'estimated_price' => 0
        ];
    }

    public function removeItem($index)
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        } else {
            $this->error('Minimal harus ada 1 baris barang/jasa.');
        }
    }

    public function save()
    {
        $data = $this->validate([
            'package_category' => 'required|string|max:255',
            'budget_year' => 'required|integer|min:2020',
            'funding_source' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'required|string',
            'items.*.estimated_price' => 'required|numeric|min:0',
        ]);

        if ($this->isEdit) {
            $this->procurement->refresh();

            if ($this->procurement->status !== ProcurementRequest::STATUS_DRAFT) {
                $this->error('Gagal menyimpan! Pengajuan ini sudah di-submit atau diproses dan tidak dapat diubah kembali.');
                return $this->redirectRoute('procurement.index', navigate: true);
            }
        }

        DB::transaction(function () use ($data) {
            $processedItems = collect($this->items)->map(function ($item, $index) {
                return [
                    'line_number' => $index + 1,
                    'item_name' => $item['item_name'],
                    'specification' => $item['specification'] ?: '-',
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'estimated_price' => $item['estimated_price'],
                    'official_price' => null,
                    'is_pph' => false,
                ];
            })->toArray();

            if ($this->isEdit) {
                if (auth()->user()->isOwner() || auth()->user()->isAdminCv()) {
                    $data['school_id'] = $this->school_id;
                }

                $this->procurement->update($data);
                $this->procurement->items()->delete();
                $this->procurement->items()->createMany($processedItems);
            } else {
                $data['uuid'] = (string) Str::uuid();
                $data['status'] = ProcurementRequest::STATUS_DRAFT;
                $data['school_id'] = auth()->user()->school_id ?? $this->school_id;

                $this->procurement = ProcurementRequest::create($data);
                $this->procurement->items()->createMany($processedItems);
            }
        });

        session()->flash('toast_success', 'Data pengajuan berhasil disimpan.');

        return $this->redirectRoute('procurement.show', ['procurementRequest' => $this->procurement->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.procurement.procurement-request-form', [
            'schools' => School::all()
        ])->layout('layouts.app');
    }
}
