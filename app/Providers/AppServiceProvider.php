<?php

namespace App\Providers;

use App\Models\MonitoringEvent;
use App\Models\User;
use App\Observers\MonitoringEventObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Observer MonitoringEvent
        |--------------------------------------------------------------------------
        */

        MonitoringEvent::observe(
            MonitoringEventObserver::class
        );

        /*
        |--------------------------------------------------------------------------
        | Developer Superuser
        |--------------------------------------------------------------------------
        |
        | Developer otomatis mendapat seluruh permission,
        | termasuk permission yang ditambahkan kemudian.
        |
        */

        Gate::before(
            function (
                User $user,
                string $ability
            ): ?bool {
                return $user->hasRole(
                    'developer'
                )
                    ? true
                    : null;
            }
        );
    }
}