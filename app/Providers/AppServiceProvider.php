<?php

namespace App\Providers;

use App\Services\KiteSessionManager;
use Illuminate\Support\ServiceProvider;
use KiteConnect\KiteConnect;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(KiteSessionManager::class, fn () => new KiteSessionManager());

        $this->app->singleton(KiteConnect::class, function ($app) {
            return $app->make(KiteSessionManager::class)->makeClient();
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
