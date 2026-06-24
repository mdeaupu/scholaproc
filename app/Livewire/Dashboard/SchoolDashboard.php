<?php

namespace App\Livewire\Dashboard;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SchoolDashboard extends Component
{
    public function render()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $school = $user->school;

        return view('livewire.dashboard.school-dashboard', [
            'schoolName' => $school?->name ?? 'Sekolah Belum Terpilih',
            'activeCount' => $school ? $school->activeRequestsCount() : 0,
            'completedCount' => $school ? $school->completedRequestsCount() : 0,
            'recentRequests' => $school ? $school->procurementRequests()->latest()->take(5)->get() : collect(),
        ])->layout('layouts.app');
    }
}
