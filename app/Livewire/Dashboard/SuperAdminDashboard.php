<?php

namespace App\Livewire\Dashboard;

use App\Models\ProcurementRequest;
use App\Models\School;
use Livewire\Component;

class SuperAdminDashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard.superadmin-dashboard', [
            'totalSchools' => School::where('status', 'active')->count(),
            'totalSubmittedRequests' => ProcurementRequest::submitted()->count(),
            'totalCompletedRequests' => ProcurementRequest::completed()->count(),
            'grandTotalEstimated' => ProcurementRequest::getTotalEstimatedAmount(),
            'grandTotalOfficial' => ProcurementRequest::getTotalOfficialAmount(),
        ])->layout('layouts.app');
    }
}
