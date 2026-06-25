<?php

use App\Http\Controllers\Auth\AuthController;
use App\Livewire\Dashboard\CvDashboard;
use App\Livewire\Dashboard\OwnerDashboard;
use App\Livewire\Dashboard\SchoolDashboard;
use App\Livewire\School\SchoolForm;
use App\Livewire\School\SchoolIndex;
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

        if ($user->isOwner()) {
            return redirect()->route('dashboard.owner');
        }
        if ($user->isAdminCv()) {
            return redirect()->route('dashboard.cv');
        }
        return redirect()->route('dashboard.school');
    })->name('dashboard');

    Route::middleware('can:owner-only')->prefix('owner')->group(function () {
        Route::get('/dashboard', OwnerDashboard::class)->name('dashboard.owner');
        Route::get('/schools', SchoolIndex::class)->name('schools.index');
        Route::get('/schools/create', SchoolForm::class)->name('schools.create');
        Route::get('/schools/{school}/edit', SchoolForm::class)->name('schools.edit');
        Route::put('/user/{user}/reset-password', [AuthController::class, 'resetPassword'])
            ->name('auth.reset-password');
    });

    Route::middleware('can:admin-cv-only')->prefix('cv')->group(function () {
        Route::get('/dashboard', CvDashboard::class)->name('dashboard.cv');
    });

    Route::middleware('can:admin-school-only')->prefix('school')->group(function () {
        Route::get('/dashboard', SchoolDashboard::class)->name('dashboard.school');
    });

    Route::view('profile', 'profile')->name('profile');
});

if (file_exists(__DIR__ . '/auth.php')) {
    require __DIR__ . '/auth.php';
}