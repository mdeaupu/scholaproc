<?php

namespace App\Livewire\Dashboard;

use App\Models\BudgetYear;
use App\Models\GeneratedDocument;
use App\Models\ProcurementRequest;
use App\Models\School;
use App\Models\User;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class SuperAdminDashboard extends Component
{
    public ?int $budgetYearId = null;

    public ?int $schoolId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function resetFilters(): void
    {
        $this->reset(['budgetYearId', 'schoolId', 'dateFrom', 'dateTo']);
    }

    public function render()
    {
        $filteredRequests = fn () => ProcurementRequest::query()
            ->when($this->budgetYearId, fn ($query) => $query->where('budget_year_id', $this->budgetYearId))
            ->when($this->schoolId, fn ($query) => $query->where('school_id', $this->schoolId))
            ->inPeriod($this->dateFrom, $this->dateTo);

        return view('livewire.dashboard.superadmin-dashboard', [
            'totalSchools' => School::where('status', 'active')->count(),
            'totalActiveUsers' => User::active()->count(),
            'totalAdminUsers' => User::active()->admin()->count(),
            'totalSchoolUsers' => User::active()->school()->count(),
            'totalRequests' => $filteredRequests()->count(),
            'totalSubmittedRequests' => $filteredRequests()->submitted()->count(),
            'totalCompletedRequests' => $filteredRequests()->completed()->count(),
            'statusBreakdown' => $filteredRequests()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'grandTotalEstimated' => ProcurementRequest::sumEstimatedAmount($filteredRequests()),
            'grandTotalOfficial' => ProcurementRequest::sumOfficialAmount($filteredRequests()),
            'totalDocuments' => GeneratedDocument::whereHas('procurementRequest', $filteredRequests)->count(),
            'recentActivity' => Activity::with('causer')->latest()->take(8)->get(),
            'budgetYears' => BudgetYear::orderByDesc('name')->get(['id', 'name', 'is_active']),
            'schools' => School::where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app');
    }
}
