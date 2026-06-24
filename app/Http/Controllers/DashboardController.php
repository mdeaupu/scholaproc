<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\ProcurementRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function ownerDashboard(): View
    {
        return view('dashboard.owner', [
            'totalSchools' => School::where('status', 'active')->count(),
            'totalSubmittedRequests' => ProcurementRequest::submitted()->count(),
            'totalCompletedRequests' => ProcurementRequest::completed()->count(),
            'grandTotalEstimated' => ProcurementRequest::getTotalEstimatedAmount(),
            'grandTotalOfficial' => ProcurementRequest::getTotalOfficialAmount(),
        ]);
    }

    public function adminCvDashboard(): View
    {
        return view('dashboard.cv', [
            'pendingVerifications' => ProcurementRequest::submitted()->with('school')->latest()->get(),
            'totalActiveProcesses' => ProcurementRequest::whereNotIn('status', ['completed', 'rejected'])->count(),
        ]);
    }

    public function adminSchoolDashboard(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $school = $user->school;

        return view('dashboard.school', [
            'schoolName' => $school?->name ?? 'Sekolah Belum Terpilih',
            'activeCount' => $school ? $school->activeRequestsCount() : 0,
            'completedCount' => $school ? $school->completedRequestsCount() : 0,
            'recentRequests' => $school ? $school->procurementRequests()->latest()->take(5)->get() : collect(),
        ]);
    }
}
