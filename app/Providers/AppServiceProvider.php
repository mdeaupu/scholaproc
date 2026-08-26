<?php

namespace App\Providers;

use App\Models\GeneratedDocument;
use App\Models\ProcurementNegotiation;
use App\Models\ProcurementVerificationFile;
use App\Models\User;
use App\Observers\GeneratedDocumentObserver;
use App\Observers\ProcurementNegotiationObserver;
use App\Observers\ProcurementVerificationFileObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('admin-school-only', function (User $user) {
            return $user->isSchool();
        });

        GeneratedDocument::observe(GeneratedDocumentObserver::class);
        ProcurementNegotiation::observe(ProcurementNegotiationObserver::class);
        ProcurementVerificationFile::observe(ProcurementVerificationFileObserver::class);
    }
}
