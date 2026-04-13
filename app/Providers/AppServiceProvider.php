<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use KiteConnect\KiteConnect;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(KiteConnect::class, function ($app) {
            $kite = new KiteConnect(
                $app['config']->get('kite.api_key'),
                $app['config']->get('kite.access_token'),
            );

            // Set the access token if available (enables authenticated calls)
            $accessToken = $app['config']->get('kite.access_token');
            if ($accessToken) {
                $kite->setAccessToken($accessToken);
            }

            return $kite;
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
