<?php

namespace App\Http\Middleware;

use App\Services\KiteSessionManager;
use App\Support\ApplicationLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidKiteSession
{
    public function __construct(private readonly KiteSessionManager $kiteSessionManager)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->kiteSessionManager->hasActiveSession()) {
            ApplicationLogger::warning('Blocked request because Zerodha session is inactive.', [
                'route_name' => optional($request->route())->getName(),
                'path' => $request->path(),
            ]);

            return redirect()
                ->route('home')
                ->with('error', 'Your Zerodha session expired. Please connect Zerodha again to open the live dashboard.');
        }

        return $next($request);
    }
}
