<?php

use App\Support\ApplicationLogger;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'app.track' => \App\Http\Middleware\TrackApplicationRequests::class,
            'kite.session' => \App\Http\Middleware\EnsureValidKiteSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $exception) {
            ApplicationLogger::error(
                'Unhandled application exception reported.',
                ApplicationLogger::exceptionContext($exception)
            );
        });
    })->create();
