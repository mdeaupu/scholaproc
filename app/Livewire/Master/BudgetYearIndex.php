<?php

namespace App\Livewire\Master;

use App\Models\BudgetYear;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class BudgetYearIndex extends Component
{
    use WithPagination, Toast;

    public bool $modal = false;
    public ?BudgetYear $budgetYear = null;

    public string $name = '';
    public string $start_date = '';
    public string $end_date = '';

    public function mount()
    {
        abort_unless(auth()->user()->role === 'superadmin' || auth()->user()->role === 'admin', 403);
    }

    public function openModal(?BudgetYear $budgetYear = null)
    {
        $this->resetValidation();
        $this->budgetYear = $budgetYear;

        if ($budgetYear && $budgetYear->exists) {
            $this->name = $budgetYear->name;
            $this->start_date = $budgetYear->start_date
                ? Carbon::parse($budgetYear->start_date)->format('Y-m-d')
                : '';
            $this->end_date = $budgetYear->end_date
                ? Carbon::parse($budgetYear->end_date)->format('Y-m-d')
                : '';
        } else {
            $this->reset(['name', 'start_date', 'end_date']);
        }

        $this->modal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => [
                'required',
                'string',
                'max:20',
                Rule::unique('budget_years', 'name')->ignore($this->budgetYear?->id),
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $isOverlapping = (new BudgetYear)->overlapsWith(
            $this->start_date,
            $this->end_date,
            $this->budgetYear?->id
        );

        if ($isOverlapping) {
            $this->addError('start_date', 'Rentang tanggal ini tumpang tindih dengan tahun anggaran lain.');
            return;
        }

        if ($this->budgetYear && $this->budgetYear->exists) {
            $this->budgetYear->update([
                'name' => $this->name,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
            ]);
            $this->success('Tahun Anggaran berhasil diperbarui.');
        } else {
            BudgetYear::create([
                'name' => $this->name,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'is_active' => false,
            ]);
            $this->success('Tahun Anggaran baru berhasil ditambahkan.');
        }

        $this->modal = false;
    }

    public function activateYear(BudgetYear $budgetYear)
    {
        if (!$budgetYear->isCurrentlyActive()) {
            $budgetYear->activate();
            $this->success("Tahun Anggaran {$budgetYear->name} sekarang aktif.");
        }
    }

    public function delete(BudgetYear $budgetYear)
    {
        if ($budgetYear->procurementRequests()->exists() || $budgetYear->documentNumberSequences()->exists()) {
            $this->error('Data gagal dihapus karena sudah dipakai dalam transaksi atau sequence dokumen.');
            return;
        }

        $budgetYear->delete();
        $this->success('Tahun Anggaran berhasil dihapus.');
    }

    public function render()
    {
        $headers = [
            ['key' => 'name', 'label' => 'Periode Anggaran'],
            ['key' => 'start_date', 'label' => 'Mulai'],
            ['key' => 'end_date', 'label' => 'Selesai'],
            ['key' => 'is_active', 'label' => 'Status'],
            ['key' => 'actions', 'label' => 'Aksi', 'sortable' => false],
        ];

        return view('livewire.master.budget-year-index', [
            'budgetYears' => BudgetYear::orderBy('start_date', 'desc')->paginate(10),
            'headers' => $headers
        ])->layout('layouts.app');
    }
}