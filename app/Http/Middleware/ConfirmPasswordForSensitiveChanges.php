<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ConfirmPasswordForSensitiveChanges
{
    public function __construct(private RequirePassword $requirePassword, private ThrottleRequests $throttle) {}

    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        if ($request->isMethodSafe() || ($ability && $request->user()->cannot($ability, array_values($request->route()->parameters())))) {
            return $next($request);
        }

        if ($request->has('confirm_password')) {
            return $this->throttle->handle($request, fn (Request $request) => $this->confirmAndReplay($request, $next), 6, 1, 'password.confirm');
        }

        $response = $this->requirePassword->handle($request, $next);

        if ($response->isRedirect(route('password.confirm'))) {
            $secrets = ['password', 'password_confirmation', 'current_password'];
            $request->session()->put('password_confirm', [
                'input' => Arr::except($request->post(), $secrets),
                // Files and passwords are not kept, so a form with one is refilled for another submit instead of saved.
                'url' => $request->allFiles() || $request->hasAny($secrets) ? null : $request->fullUrl(),
                'method' => $request->method(),
                'previous' => url()->previous(),
            ]);
        }

        return $response;
    }

    private function confirmAndReplay(Request $request, Closure $next): Response
    {
        $pending = $request->session()->get('password_confirm', []);

        if (($pending['url'] ?? null) !== $request->fullUrl()) {
            return redirect()->route('password.confirm');
        }

        if (! Auth::guard('web')->validate(['email' => $request->user()->email, 'password' => $request->confirm_password])) {
            return redirect()->route('password.confirm')->withErrors(['password' => __('auth.password')]);
        }

        $request->session()->forget('password_confirm');
        $request->session()->put('auth.password_confirmed_at', time());
        $request->replace($pending['input']);
        $request->headers->set('referer', $pending['previous']);

        return $next($request);
    }
}
