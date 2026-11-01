<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->must_change_password || $request->routeIs('password.edit', 'password.update', 'logout')) {
            return $next($request);
        }

        abort_if($request->expectsJson(), Response::HTTP_FORBIDDEN, __('You must change your password before continuing.'));

        return redirect()->guest(route('password.edit'));
    }
}
