<?php

namespace App\Livewire\Procurement;

use App\Models\BudgetYear;
use App\Models\FundingSource;
use App\Models\ProcurementRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class ProcurementRequestList extends Component
{
    use WithPagination, Toast;

    public string $search = '';

    public string $filterStatus = '';

    public string $filterBudgetYear = '';

    public string $filterFundingSource = '';

    public bool $confirmingDeleteDraft = false;

    public ?int $targetDraftId = null;

    public string $targetDraftNumber = '';

    public function mount(): void
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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterBudgetYear(): void
    {
        $this->resetPage();
    }

    public function updatingFilterFundingSource(): void
    {
        $this->resetPage();
    }

    public function confirmDeleteDraft(int $id): void
    {
        $user = auth()->user();
        abort_unless($user->isSchool(), 403);

        $request = $this->schoolRequests()
            ->where('status', ProcurementRequest::STATUS_DRAFT)
            ->findOrFail($id);

        $this->targetDraftId = $request->id;
        $this->targetDraftNumber = 'PGN-' . $request->created_at->format('Y') . '-'
            . str_pad((string) $request->id, 4, '0', STR_PAD_LEFT);
        $this->confirmingDeleteDraft = true;
    }

    public function deleteConfirmed(): void
    {
        abort_unless($this->targetDraftId !== null, 404);

        $this->delete($this->targetDraftId);
    }

    public function delete(int $id): void
    {
        $user = auth()->user();
        abort_unless($user->isSchool(), 403);

        $request = $this->schoolRequests()
            ->where('status', ProcurementRequest::STATUS_DRAFT)
            ->findOrFail($id);

        DB::transaction(function () use ($request): void {
            $request->items->each(fn ($item) => $item->delete());
            $request->delete();
        });

        $this->confirmingDeleteDraft = false;
        $this->targetDraftId = null;
        $this->targetDraftNumber = '';
        $this->success('Draft pengajuan berhasil dihapus.');
    }

    public function render()
    {
        $baseQuery = $this->visibleRequests();
        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $requests = (clone $baseQuery)
            ->with(['packageCategory', 'fundingSource', 'budgetYear'])
            ->withSum('items as estimated_subtotal', DB::raw('quantity * estimated_price'))
            ->when($this->filterStatus !== '', fn (Builder $query) => $query->where('status', $this->filterStatus))
            ->when($this->filterBudgetYear !== '', fn (Builder $query) => $query->where('budget_year_id', $this->filterBudgetYear))
            ->when($this->filterFundingSource !== '', fn (Builder $query) => $query->where('funding_source_id', $this->filterFundingSource))
            ->when($this->search !== '', function (Builder $query): void {
                $search = trim($this->search);
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('uuid', 'like', '%' . $search . '%')
                        ->orWhereHas('packageCategory', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'like', '%' . $search . '%'));

                    if (preg_match('/^PGN-(\d{4})-(\d+)$/i', $search, $matches) === 1) {
                        $query->orWhere(function (Builder $referenceQuery) use ($matches): void {
                            $referenceQuery->whereYear('created_at', (int) $matches[1])
                                ->whereKey((int) $matches[2]);
                        });
                    } elseif (ctype_digit($search)) {
                        $query->orWhereKey((int) $search);
                    }
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.procurement.procurement-request-list', [
            'requests' => $requests,
            'stats' => [
                'total' => $statusCounts->sum(),
                'draft' => (int) ($statusCounts[ProcurementRequest::STATUS_DRAFT] ?? 0),
                'submitted' => (int) ($statusCounts[ProcurementRequest::STATUS_SUBMITTED] ?? 0),
                'needs_revision' => (int) ($statusCounts[ProcurementRequest::STATUS_NEEDS_REVISION] ?? 0),
                'price_agreed' => (int) ($statusCounts[ProcurementRequest::STATUS_PRICE_AGREED] ?? 0),
                'completed' => (int) ($statusCounts[ProcurementRequest::STATUS_COMPLETED] ?? 0),
            ],
            'statusOptions' => ProcurementRequest::STATUS_LABELS,
            'budgetYears' => BudgetYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'fundingSources' => FundingSource::query()->orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app');
    }

    private function visibleRequests(): Builder
    {
        $user = auth()->user();
        $query = ProcurementRequest::query();

        if ($user->isSchool()) {
            return $this->schoolRequests();
        }

        return $query;
    }

    private function schoolRequests(): Builder
    {
        $schoolId = auth()->user()->school_id;

        return ProcurementRequest::query()
            ->when($schoolId, fn (Builder $query) => $query->where('school_id', $schoolId))
            ->when(! $schoolId, fn (Builder $query) => $query->whereRaw('1 = 0'));
    }
}
