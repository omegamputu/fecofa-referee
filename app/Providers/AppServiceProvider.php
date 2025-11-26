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
        // Le Super-Admin passe avant tous les checks
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Owner') ? true : null;
        });

        Gate::policy(User::class, \App\Policies\UserPolicy::class);
        Gate::policy(\App\Models\League::class, \App\Policies\LeaguePolicy::class);
    }
}
