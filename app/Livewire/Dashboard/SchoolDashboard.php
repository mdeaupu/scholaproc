<?php

namespace App\Livewire\Dashboard;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SchoolDashboard extends Component
{
    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        $school = $user->school;

        $recentNotifications = collect();
        if ($school) {
            $recentNotifications = Notification::whereHas('procurementRequest', fn ($q) => $q->where('school_id', $school->id))
                ->whereIn('event_type', Notification::SCHOOL_EVENTS)
                ->latest()
                ->take(5)
                ->get();
        }

        return view('livewire.dashboard.school-dashboard', [
            'schoolName' => $school?->name ?? 'Sekolah Belum Terpilih',
            'activeCount' => $school ? $school->activeRequestsCount() : 0,
            'completedCount' => $school ? $school->completedRequestsCount() : 0,
            'recentRequests' => $school ? $school->procurementRequests()->latest()->take(5)->get() : collect(),
            'recentNotifications' => $recentNotifications,
        ])->layout('layouts.app');
    }
}
