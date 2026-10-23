<?php

namespace App\Livewire\Dashboard;

use App\Models\ProcurementNegotiation;
use App\Models\ProcurementRequest;
use Livewire\Component;

class AdminDashboard extends Component
{
    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function resetFilters(): void
    {
        $this->reset(['dateFrom', 'dateTo']);
    }

    public function render()
    {
        $filteredRequests = fn () => ProcurementRequest::query()
            ->inPeriod($this->dateFrom, $this->dateTo);

        $pendingNegotiationsQuery = ProcurementNegotiation::pending()
            ->offeredBy(ProcurementNegotiation::OFFERED_BY_SCHOOL);

        return view('livewire.dashboard.admin-dashboard', [
            'pendingVerifications' => $filteredRequests()
                ->submitted()
                ->with('school')
                ->latest()
                ->limit(10)
                ->get(),
            'pendingVerificationCount' => $filteredRequests()->submitted()->count(),
            'totalActiveProcesses' => $filteredRequests()->active()->count(),
            'pendingNegotiations' => (clone $pendingNegotiationsQuery)
                ->with(['procurementRequest.school', 'user'])
                ->latest()
                ->limit(5)
                ->get(),
            'pendingNegotiationCount' => (clone $pendingNegotiationsQuery)->count(),
            'processingCount' => $filteredRequests()
                ->where('status', ProcurementRequest::STATUS_PROCESSING)
                ->count(),
            'partiallyReceivedCount' => $filteredRequests()
                ->where('status', ProcurementRequest::STATUS_PARTIALLY_RECEIVED)
                ->count(),
            'receivedCount' => $filteredRequests()
                ->where('status', ProcurementRequest::STATUS_RECEIVED)
                ->count(),
            'invoicedCount' => $filteredRequests()
                ->where('status', ProcurementRequest::STATUS_INVOICED)
                ->count(),
            'paymentPendingCount' => $filteredRequests()
                ->where('status', ProcurementRequest::STATUS_PAYMENT_PENDING)
                ->count(),
            'disputedCount' => $filteredRequests()
                ->where('status', ProcurementRequest::STATUS_DISPUTED)
                ->count(),
        ])->layout('layouts.app');
    }
}
