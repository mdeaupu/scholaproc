<?php

namespace App\Providers;

use App\Models\User;
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
        Gate::define('owner-only', function (User $user) {
            return $user->isOwner();
        });
        Gate::define('admin-cv-only', function (User $user) {
            return $user->isAdminCv();
        });
        Gate::define('admin-school-only', function (User $user) {
            return $user->isAdminSchool();
        });
    }
}
