<?php

namespace App\Livewire\Dashboard;

use App\Models\GeneratedDocument;
use App\Models\Notification;
use App\Models\ProcurementNegotiation;
use App\Models\ProcurementRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SchoolDashboard extends Component
{
    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function resetFilters(): void
    {
        $this->reset(['dateFrom', 'dateTo']);
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        $school = $user->school;

        $schoolRequests = $school
            ? $school->procurementRequests()->inPeriod($this->dateFrom, $this->dateTo)
            : ProcurementRequest::whereKey(-1);

        return view('livewire.dashboard.school-dashboard', [
            'schoolName' => $school?->name ?? 'Sekolah Belum Terpilih',
            'activeCount' => (clone $schoolRequests)->active()->count(),
            'completedCount' => (clone $schoolRequests)->completed()->count(),
            'pendingNegotiationCount' => $school
                ? ProcurementNegotiation::pending()
                    ->offeredBy(ProcurementNegotiation::OFFERED_BY_CV)
                    ->whereHas('procurementRequest', fn ($query) => $query->where('school_id', $school->id))
                    ->count()
                : 0,
            'paymentPendingCount' => (clone $schoolRequests)->whereIn('status', [
                ProcurementRequest::STATUS_INVOICED,
                ProcurementRequest::STATUS_PAYMENT_PENDING,
            ])->count(),
            'recentRequests' => (clone $schoolRequests)->with('items')->latest()->take(5)->get(),
            'recentDocuments' => $school
                ? GeneratedDocument::whereHas('procurementRequest', fn ($query) => $query->where('school_id', $school->id))
                    ->with('procurementRequest')
                    ->latest('generated_at')
                    ->take(5)
                    ->get()
                : collect(),
            'recentNotifications' => $school
                ? Notification::whereHas('procurementRequest', fn ($query) => $query->where('school_id', $school->id))
                    ->latest()
                    ->take(5)
                    ->get()
                : collect(),
        ])->layout('layouts.app');
    }
}
