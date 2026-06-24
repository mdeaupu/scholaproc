<?php

namespace App\Livewire\Dashboard;

use App\Models\ProcurementRequest;
use Livewire\Component;

class CvDashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard.cv-dashboard', [
            'pendingVerifications' => ProcurementRequest::submitted()->with('school')->latest()->get(),
            'totalActiveProcesses' => ProcurementRequest::whereNotIn('status', ['completed', 'rejected'])->count(),
        ])->layout('layouts.app');
    }
}
