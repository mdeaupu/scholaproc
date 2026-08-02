<?php

use App\Http\Controllers\Auth\AuthController;
use App\Livewire\Dashboard\SuperAdminDashboard;
use App\Livewire\Dashboard\AdminDashboard;
use App\Livewire\Dashboard\SchoolDashboard;
use App\Livewire\School\SchoolForm;
use App\Livewire\School\SchoolIndex;
use App\Livewire\Supplier\LegalDocumentForm;
use App\Livewire\Supplier\SupplierForm;
use App\Livewire\Supplier\SupplierIndex;
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
        Route::get('/dashboard', SuperAdminDashboard::class)->name('dashboard.superadmin');
        Route::get('/schools', SchoolIndex::class)->name('schools.index');
        Route::get('/schools/create', SchoolForm::class)->name('schools.create');
        Route::get('/schools/{school}/edit', SchoolForm::class)->name('schools.edit');
        Route::get('/suppliers', SupplierIndex::class)->name('superadmin.suppliers.index');
        Route::get('/suppliers/create', SupplierForm::class)->name('superadmin.suppliers.create');
        Route::get('/suppliers/{supplier}/edit', SupplierForm::class)->name('superadmin.suppliers.edit');
        Route::get('/suppliers/{supplier}/legal-documents/create', LegalDocumentForm::class)->name('superadmin.suppliers.legal-documents.create');
        Route::get('/suppliers/{supplier}/legal-documents/{document}/edit', LegalDocumentForm::class)->name('superadmin.suppliers.legal-documents.edit');
        Route::put('/user/{user}/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', AdminDashboard::class)->name('dashboard.admin');
        Route::get('/suppliers', SupplierIndex::class)->name('admin.suppliers.index');
        Route::get('/suppliers/create', SupplierForm::class)->name('admin.suppliers.create');
        Route::get('/suppliers/{supplier}/edit', SupplierForm::class)->name('admin.suppliers.edit');
        Route::get('/suppliers/{supplier}/legal-documents/create', LegalDocumentForm::class)->name('admin.suppliers.legal-documents.create');
        Route::get('/suppliers/{supplier}/legal-documents/{document}/edit', LegalDocumentForm::class)->name('admin.suppliers.legal-documents.edit');
    });

    Route::middleware('role:school')->prefix('school')->group(function () {
        Route::get('/dashboard', SchoolDashboard::class)->name('dashboard.school');
    });

    Route::view('profile', 'profile')->name('profile');
});

if (file_exists(__DIR__ . '/auth.php')) {
    require __DIR__ . '/auth.php';
}