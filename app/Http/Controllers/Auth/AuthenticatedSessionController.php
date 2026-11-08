<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'page_name' => __('Login'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        return redirect($this->redirectTarget($request) ?? route('dashboard', absolute: false));
    }

    private function redirectTarget(Request $request): ?string
    {
        $name = $request->query('redirect');
        $parameters = $request->query('parameters', []);

        if (! is_string($name) || ! is_array($parameters) || ! in_array('GET', Route::getRoutes()->getByName($name)?->methods() ?? [])) {
            return null;
        }

        try {
            return route($name, $parameters, absolute: false);
        } catch (UrlGenerationException) {
            return null;
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
