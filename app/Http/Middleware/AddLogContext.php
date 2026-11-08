<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddLogContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $request_id = (string) Str::uuid();

        Context::add([
            'request_id' => $request_id,
            'method' => $request->method(),
            // The route's pattern rather than its URL, so tokens in login and reset links stay out of the logs.
            'uri' => $request->route()?->uri(),
            'route' => $request->route()?->getName(),
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $request_id);

        return $response;
    }
}
