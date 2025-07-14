<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Support\RemoteAuth;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('remoteauth', function () {
            return new RemoteAuth();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
