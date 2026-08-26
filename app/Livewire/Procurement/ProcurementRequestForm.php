<?php

namespace App\Livewire\Procurement;

use App\Models\BudgetYear;
use App\Models\FundingSource;
use App\Models\ItemUnit;
use App\Models\PackageCategory;
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
    public $package_category_id = '';
    public $budget_year_id = '';
    public $funding_source_id = '';

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

            if (!$user->isSchool()) {
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
            $this->package_category_id = $this->procurement->package_category_id;
            $this->budget_year_id = $this->procurement->budget_year_id;
            $this->funding_source_id = $this->procurement->funding_source_id;

            $this->items = $this->procurement->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'specification' => $item->specification ?? '',
                    'quantity' => $item->quantity,
                    'unit_id' => $item->unit_id,
                    'estimated_price' => $item->estimated_price,
                ];
            })->toArray();
        } else {
            if ($user->isSchool()) {
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
            'unit_id' => '',
            'estimated_price' => 0,
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
        $this->validate([
            'package_category_id' => 'required|exists:package_categories,id',
            'budget_year_id' => 'required|exists:budget_years,id',
            'funding_source_id' => 'required|exists:funding_sources,id',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.specification' => 'nullable|string|max:1000',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_id' => 'required|exists:item_units,id',
            'items.*.estimated_price' => 'required|numeric|min:0',
        ]);

        if ($this->isEdit) {
            $this->procurement->refresh();

            if ($this->procurement->status !== ProcurementRequest::STATUS_DRAFT) {
                $this->error('Gagal menyimpan! Pengajuan ini sudah di-submit atau diproses dan tidak dapat diubah kembali.');
                return $this->redirectRoute('procurement.index', navigate: true);
            }
        }

        DB::transaction(function () {
            $processedItems = collect($this->items)->map(function ($item, $index) {
                return [
                    'line_number' => $index + 1,
                    'item_name' => $item['item_name'],
                    'specification' => $item['specification'] ?: '-',
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'estimated_price' => $item['estimated_price'],
                    'official_price' => null,
                    'is_pph' => false,
                    'negotiation_status' => 'not_started',
                ];
            })->toArray();

            $data = [
                'school_id' => $this->school_id,
                'package_category_id' => $this->package_category_id,
                'budget_year_id' => $this->budget_year_id,
                'funding_source_id' => $this->funding_source_id,
            ];

            if ($this->isEdit) {
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
            'schools' => School::all(),
            'packageCategories' => PackageCategory::where('is_active', true)->get(),
            'fundingSources' => FundingSource::where('is_active', true)->get(),
            'budgetYears' => BudgetYear::all(),
            'itemUnits' => ItemUnit::where('is_active', true)->get(),
        ])->layout('layouts.app');
    }
}
