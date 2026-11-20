<?php

namespace App\Http\Middleware;

use App\Features\ConfirmPasswordOnSave;
use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;

class ConfirmPasswordForSensitiveChanges
{
    public function __construct(private RequirePassword $requirePassword) {}

    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        if ($ability && $request->user()->cannot($ability, array_values($request->route()->parameters()))) {
            return $next($request);
        }

        if ($request->isMethodSafe() && Feature::active(ConfirmPasswordOnSave::class)) {
            return $next($request);
        }

        $response = $this->requirePassword->handle($request, $next);

        if ($response->isRedirect(route('password.confirm'))) {
            $request->session()->put('password_confirm.input', Arr::except($request->post(), ['password', 'password_confirmation', 'current_password']));
        }

        return $response;
    }
}
