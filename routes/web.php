<?php

use App\Http\Controllers\AuthController;
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
        Route::view('/dashboard', 'dashboard.owner')->name('dashboard.owner');
        Route::put('/user/{user}/reset-password', [AuthController::class, 'resetPassword'])
            ->name('auth.reset-password');
    });

    Route::middleware('can:admin-cv-only')->prefix('cv')->group(function () {
        Route::view('/dashboard', 'dashboard.cv')->name('dashboard.cv');
    });

    Route::middleware('can:admin-school-only')->prefix('school')->group(function () {
        Route::view('/dashboard', 'dashboard.school')->name('dashboard.school');
    });

    Route::view('profile', 'profile')->name('profile');
});

if (file_exists(__DIR__ . '/auth.php')) {
    require __DIR__ . '/auth.php';
}