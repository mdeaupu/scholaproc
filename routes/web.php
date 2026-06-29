<?php

use App\Http\Controllers\Auth\AuthController;
use App\Livewire\Admin\AdminIndex;
use App\Livewire\Dashboard\CvDashboard;
use App\Livewire\Dashboard\OwnerDashboard;
use App\Livewire\Dashboard\SchoolDashboard;
use App\Livewire\Procurement\ProcurementProcessPanel;
use App\Livewire\Procurement\ProcurementRequestForm;
use App\Livewire\Procurement\ProcurementRequestList;
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
        Route::get('/admins', AdminIndex::class)->name('admins.index');
        Route::get('/schools', SchoolIndex::class)->name('schools.index');
        Route::get('/schools/create', SchoolForm::class)->name('schools.create');
        Route::get('/schools/{school}/edit', SchoolForm::class)->name('schools.edit');
        Route::get('/suppliers', SupplierIndex::class)->name('owner.suppliers.index');
        Route::get('/suppliers/create', SupplierForm::class)->name('owner.suppliers.create');
        Route::get('/suppliers/{supplier}/edit', SupplierForm::class)->name('owner.suppliers.edit');
        Route::get('/suppliers/{supplier}/legal-documents/create', LegalDocumentForm::class)->name('owner.suppliers.legal-documents.create');
        Route::get('/suppliers/{supplier}/legal-documents/{document}/edit', LegalDocumentForm::class)->name('owner.suppliers.legal-documents.edit');
        Route::put('/user/{user}/reset-password', [AuthController::class, 'resetPassword'])
            ->name('auth.reset-password');
    });

    Route::middleware('can:admin-cv-only')->prefix('cv')->group(function () {
        Route::get('/dashboard', CvDashboard::class)->name('dashboard.cv');
        Route::get('/suppliers', SupplierIndex::class)->name('cv.suppliers.index');
        Route::get('/suppliers/create', SupplierForm::class)->name('cv.suppliers.create');
        Route::get('/suppliers/{supplier}/edit', SupplierForm::class)->name('cv.suppliers.edit');
        Route::get('/suppliers/{supplier}/legal-documents/create', LegalDocumentForm::class)->name('cv.suppliers.legal-documents.create');
        Route::get('/suppliers/{supplier}/legal-documents/{document}/edit', LegalDocumentForm::class)->name('cv.suppliers.legal-documents.edit');
    });

    Route::middleware('can:admin-school-only')->prefix('school')->group(function () {
        Route::get('/dashboard', SchoolDashboard::class)->name('dashboard.school');
    });

    Route::prefix('procurement-requests')->name('procurement.')->group(function () {
        Route::get('/', ProcurementRequestList::class)->name('index');
        Route::middleware('can:admin-school-only')->group(function () {
            Route::get('/create', ProcurementRequestForm::class)->name('create');
            Route::get('/{id}/edit', ProcurementRequestForm::class)->name('edit');
        });
        Route::get('/{procurementRequest}', ProcurementProcessPanel::class)->name('show');
    });

    Route::view('profile', 'profile')->name('profile');
});

if (file_exists(__DIR__ . '/auth.php')) {
    require __DIR__ . '/auth.php';
}