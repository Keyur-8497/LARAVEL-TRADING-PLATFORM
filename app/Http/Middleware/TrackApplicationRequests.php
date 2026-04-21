<?php

namespace App\Http\Middleware;

use App\Support\ApplicationLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackApplicationRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $requestContext = ApplicationLogger::requestContext($request);

        ApplicationLogger::request('Incoming application request.', $requestContext);

        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
            ApplicationLogger::error(
                'Application request failed before response.',
                ApplicationLogger::exceptionContext($exception, $requestContext)
            );

            throw $exception;
        }

        ApplicationLogger::request('Application response sent.', array_merge($requestContext, [
            'status_code' => $response->getStatusCode(),
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
        ]));

        return $response;
    }
}
