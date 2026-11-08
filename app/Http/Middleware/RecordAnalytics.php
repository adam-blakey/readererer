<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RecordAnalytics
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! config('app.readererer_analytics')) {
            return;
        }

        try {
            DB::table('analytics_events')->insert([
                'method' => $request->method(),
                // The route's name or pattern, never the URL, which can hold IDs and query strings.
                'route' => $request->route()?->getName() ?? $request->route()?->uri(),
                'status' => $response->getStatusCode(),
                'duration_ms' => defined('LARAVEL_START') ? (int) round((microtime(true) - LARAVEL_START) * 1000) : null,
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Analytics must never break a request.
        }
    }
}
