<?php

namespace App\Livewire\Master;

use App\Models\BudgetYear;
use Illuminate\Database\Eloquent\Builder;
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

    public string $search = '';

    public string $filterStatus = '';

    public string $tahun = '';

    public string $tanggal_mulai = '';

    public string $tanggal_selesai = '';

    public bool $is_active = false;

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

    public function openModal(?BudgetYear $budgetYear = null): void
    {
        $this->authorizeSuperAdmin();
        $this->resetValidation();
        $this->budgetYear = $budgetYear;

        if ($budgetYear && $budgetYear->exists) {
            $this->tahun = $budgetYear->name;
            $this->tanggal_mulai = $budgetYear->start_date
                ? Carbon::parse($budgetYear->start_date)->format('Y-m-d')
                : '';
            $this->tanggal_selesai = $budgetYear->end_date
                ? Carbon::parse($budgetYear->end_date)->format('Y-m-d')
                : '';
            $this->is_active = $budgetYear->is_active;
        } else {
            $this->reset(['tahun', 'tanggal_mulai', 'tanggal_selesai', 'is_active']);
        }

        $this->modal = true;
    }

    public function save(): void
    {
        $this->authorizeSuperAdmin();

        $this->validate([
            'tahun' => [
                'required',
                'string',
                'max:20',
                Rule::unique('budget_years', 'name')->ignore($this->budgetYear?->id),
            ],
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'is_active' => 'boolean',
        ]);

        $isOverlapping = (new BudgetYear)->overlapsWith(
            $this->tanggal_mulai,
            $this->tanggal_selesai,
            $this->budgetYear?->id
        );

        if ($isOverlapping) {
            $this->addError('tanggal_mulai', 'Rentang tanggal ini tumpang tindih dengan tahun anggaran lain.');

            return;
        }

        $data = [
            'name' => (string) $this->tahun,
            'start_date' => $this->tanggal_mulai,
            'end_date' => $this->tanggal_selesai,
            'is_active' => false,
        ];

        if ($this->budgetYear?->exists) {
            $this->budgetYear->update($data);

            if ($this->is_active) {
                $this->budgetYear->activate();
            }

            $this->success('Tahun Anggaran berhasil diperbarui.');
        } else {
            $budgetYear = BudgetYear::create($data);

            if ($this->is_active) {
                $budgetYear->activate();
            }

            $this->success('Tahun Anggaran baru berhasil ditambahkan.');
        }

        $this->modal = false;
        $this->reset(['tahun', 'tanggal_mulai', 'tanggal_selesai', 'is_active']);
    }

    public function activateYear(BudgetYear $budgetYear): void
    {
        $this->authorizeSuperAdmin();

        if (!$budgetYear->isCurrentlyActive()) {
            $budgetYear->activate();
            $this->success("Tahun Anggaran {$budgetYear->name} sekarang aktif.");
        }
    }

    public function delete(BudgetYear $budgetYear): void
    {
        $this->authorizeSuperAdmin();

        if ($budgetYear->procurementRequests()->exists() || $budgetYear->documentNumberSequences()->exists()) {
            $this->error('Data gagal dihapus karena sudah dipakai dalam transaksi atau sequence dokumen.');

            return;
        }

        $budgetYear->delete();
        $this->success('Tahun Anggaran berhasil dihapus.');
    }

    public function render()
    {
        $this->authorizeSuperAdmin();

        $baseQuery = BudgetYear::query();
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
        ];

        $budgetYears = (clone $baseQuery)
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereDate('start_date', $this->search)
                        ->orWhereDate('end_date', $this->search);
                });
            })
            ->when($this->filterStatus === 'active', fn(Builder $query) => $query->where('is_active', true))
            ->when($this->filterStatus === 'inactive', fn(Builder $query) => $query->where('is_active', false))
            ->orderByDesc('start_date')
            ->paginate(10);

        if ($budgetYears->firstItem() !== null) {
            $budgetYears->getCollection()->each(function (BudgetYear $year, int $index) use ($budgetYears) {
                $year->setAttribute('row_no', $budgetYears->firstItem() + $index);
            });
        }

        $headers = [
            ['key' => 'row_no', 'label' => 'No.', 'class' => 'w-14'],
            ['key' => 'name', 'label' => 'Tahun Anggaran'],
            ['key' => 'start_date', 'label' => 'Tanggal Mulai'],
            ['key' => 'end_date', 'label' => 'Tanggal Selesai'],
            ['key' => 'is_active', 'label' => 'Status'],
            ['key' => 'actions', 'label' => 'Aksi', 'class' => 'text-right w-16'],
        ];

        return view('livewire.master.budget-year-index', [
            'budgetYears' => $budgetYears,
            'headers' => $headers,
            'stats' => $stats,
        ])->layout('layouts.app');
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}