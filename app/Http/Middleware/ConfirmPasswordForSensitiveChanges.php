<?php

namespace App\Http\Middleware;

use App\Features\ConfirmPasswordForSensitiveChanges as Flag;
use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;

class ConfirmPasswordForSensitiveChanges
{
    public function __construct(private RequirePassword $requirePassword) {}

    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        if (! Feature::active(Flag::class)
            || ($ability && $request->user()->cannot($ability, array_values($request->route()->parameters())))) {
            return $next($request);
        }

        return $this->requirePassword->handle($request, $next);
    }
}
