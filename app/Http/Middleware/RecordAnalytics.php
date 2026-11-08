<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordAnalytics
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        record_analytics('request', [
            'http.request.method' => $request->method(),
            'http.route' => analytics_route($request),
            'http.response.status_code' => $response->getStatusCode(),
            'duration_ms' => defined('LARAVEL_START') ? (int) round((microtime(true) - LARAVEL_START) * 1000) : null,
        ], $response->getStatusCode() >= 500);
    }
}
