<?php

use App\Livewire\Admin\AdminCreate;
use App\Livewire\Admin\AdminEdit;
use App\Livewire\Admin\AdminIndex;
use App\Livewire\Dashboard\AdminDashboard;
use App\Livewire\Dashboard\SchoolDashboard;
use App\Livewire\Dashboard\SuperAdminDashboard;
use App\Livewire\Master\BudgetYearIndex;
use App\Livewire\Master\FundingSourceIndex;
use App\Livewire\Master\ItemUnitIndex;
use App\Livewire\Master\PackageCategoryIndex;
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
        Route::get('/suppliers', SupplierIndex::class)->name('superadmin.suppliers.index');
        Route::get('/suppliers/create', SupplierForm::class)->name('superadmin.suppliers.create');
        Route::get('/suppliers/{supplier}/edit', SupplierForm::class)->name('superadmin.suppliers.edit');
        Route::get('/suppliers/{supplier}/legal-documents/create', LegalDocumentForm::class)->name('superadmin.suppliers.legal-documents.create');
        Route::get('/suppliers/{supplier}/legal-documents/{document}/edit', LegalDocumentForm::class)->name('superadmin.suppliers.legal-documents.edit');
        Route::get('/admins', AdminIndex::class)->name('admins.index');
        Route::get('/admins/create', AdminCreate::class)->name('admins.create');
        Route::get('/admins/{user}/edit', AdminEdit::class)->name('admins.edit');
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', AdminDashboard::class)->name('dashboard.admin');
        Route::get('/suppliers', SupplierIndex::class)->name('admin.suppliers.index');
    });

    Route::middleware('role:school')->prefix('school')->group(function () {
        Route::get('/dashboard', SchoolDashboard::class)->name('dashboard.school');
    });

    Route::middleware('role:superadmin|admin')->prefix('master')->name('master.')->group(function () {
        Route::get('/package-categories', PackageCategoryIndex::class)->name('package-categories');
        Route::get('/budget-years', BudgetYearIndex::class)->name('budget-years');
        Route::get('/funding-sources', FundingSourceIndex::class)->name('funding-sources');
        Route::get('/item-units', ItemUnitIndex::class)->name('item-units');
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
