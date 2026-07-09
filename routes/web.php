<?php

use App\Http\Controllers\AuthController;
use App\Livewire\Dashboard\CvDashboard;
use App\Livewire\Dashboard\OwnerDashboard;
use App\Livewire\Dashboard\SchoolDashboard;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Volt::route('login', 'auth.login')->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return redirect()->route('dashboard.superadmin');
        }
        if ($user->isAdmin()) {
            return redirect()->route('dashboard.admin');
        }
        return redirect()->route('dashboard.school');
    })->name('dashboard');

    Route::middleware('role:superadmin')->prefix('superadmin')->group(function () {
        Route::view('/dashboard', 'livewire.dashboard.superadmin')->name('dashboard.superadmin');
        Route::put('/user/{user}/reset-password', [AuthController::class, 'resetPassword'])
            ->name('auth.reset-password');
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::view('/dashboard', 'livewire.dashboard.admin')->name('dashboard.admin');
    });

    Route::middleware('role:school')->prefix('school')->group(function () {
        Route::view('/dashboard', 'livewire.dashboard.school')->name('dashboard.school');
    });

    Route::view('profile', 'profile')->name('profile');
});

if (file_exists(__DIR__ . '/auth.php')) {
    require __DIR__ . '/auth.php';
}