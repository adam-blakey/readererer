<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->session()->get('two_factor.pending') || $request->routeIs('two-factor.challenge', 'two-factor.verify', 'logout')) {
            return $next($request);
        }

        abort_if($request->expectsJson(), Response::HTTP_FORBIDDEN, __('You must enter your two-factor authentication code before continuing.'));

        return redirect()->guest(route('two-factor.challenge'));
    }
}
