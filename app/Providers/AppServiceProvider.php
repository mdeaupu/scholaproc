<?php

namespace App\Providers;

use App\Models\ProcurementNegotiation;
use App\Models\User;
use App\Observers\ProcurementNegotiationObserver;
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

        ProcurementNegotiation::observe(ProcurementNegotiationObserver::class);
    }
}
