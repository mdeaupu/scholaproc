<?php

use App\Livewire\Admin\AdminCreate;
use App\Livewire\Admin\AdminEdit;
use App\Livewire\Admin\AdminIndex;
use App\Livewire\Dashboard\AdminDashboard;
use App\Livewire\Dashboard\SchoolDashboard;
use App\Livewire\Dashboard\SuperAdminDashboard;
use App\Livewire\School\SchoolForm;
use App\Livewire\School\SchoolIndex;
use App\Livewire\Subsidiary\SubsidiaryForm;
use App\Livewire\Subsidiary\SubsidiaryIndex;
use App\Livewire\Subsidiary\SubsidiaryLegalDocumentForm;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });
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
        Route::get('/admins', AdminIndex::class)->name('admins.index');
        Route::get('/admins/create', AdminCreate::class)->name('admins.create');
        Route::get('/admins/{user}/edit', AdminEdit::class)->name('admins.edit');
        Route::get('/subsidiaries', SubsidiaryIndex::class)->name('superadmin.subsidiaries.index');
        Route::get('/subsidiaries/create', SubsidiaryForm::class)->name('superadmin.subsidiaries.create');
        Route::get('/subsidiaries/{subsidiary}/edit', SubsidiaryForm::class)->name('superadmin.subsidiaries.edit');
        Route::get('/subsidiaries/{subsidiary}/legal-documents/create', SubsidiaryLegalDocumentForm::class)->name('superadmin.subsidiaries.legal-documents.create');
        Route::get('/subsidiaries/{subsidiary}/legal-documents/{document}/edit', SubsidiaryLegalDocumentForm::class)->name('superadmin.subsidiaries.legal-documents.edit');
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', AdminDashboard::class)->name('dashboard.admin');
    });

    Route::middleware('role:school')->prefix('school')->group(function () {
        Route::get('/dashboard', SchoolDashboard::class)->name('dashboard.school');
    });

    Route::view('profile', 'profile')->name('profile');
});

if (file_exists(__DIR__.'/auth.php')) {
    require __DIR__.'/auth.php';
}
