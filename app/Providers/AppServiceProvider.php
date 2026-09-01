<?php

namespace App\Providers;

use App\Support\Navigation;
use Illuminate\Support\Facades\View;
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
        View::composer('layouts.dashboard', function ($view) {
            $roleLabel = auth()->user()?->role?->name ?? '';

            $view->with([
                'roleLabel' => $roleLabel,
                'portalLabel' => $roleLabel === 'Administrator' ? 'Admin Portal' : ($roleLabel !== '' ? $roleLabel.' Portal' : 'Portal'),
                'navGroups' => Navigation::forRole($roleLabel),
            ]);
        });
    }
}
