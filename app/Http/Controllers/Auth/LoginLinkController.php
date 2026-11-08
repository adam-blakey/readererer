<?php

namespace App\Http\Controllers\Auth;

use App\Enums\EmailLinkType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class LoginLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.login-link', [
            'page_name' => __('Email me a login link'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = EmailLinkType::LoginLink->send($request->email);

        if ($status !== Password::RESET_LINK_SENT) {
            Log::warning('Login link not sent', ['status' => $status]);
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return back()->with('status', __('If :email belongs to a member\'s account, a login link has been sent to it.', ['email' => $request->email]));
    }

    public function show(Request $request, string $token): View|RedirectResponse
    {
        if (! $this->user($request->query('email'), $token)) {
            return $this->invalid();
        }

        return view('auth.login-link-confirm', [
            'page_name' => __('Login'),
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function login(Request $request, string $token): RedirectResponse
    {
        $user = $this->user($request->input('email'), $token);

        if (! $user) {
            return $this->invalid();
        }

        Password::deleteToken($user);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function user(mixed $email, string $token): ?User
    {
        return is_string($email) ? EmailLinkType::LoginLink->user($email, $token) : null;
    }

    private function invalid(): RedirectResponse
    {
        Log::warning('Login link was invalid or had expired');

        return redirect()->route('login-link.request')->withErrors(['email' => __('This login link is invalid or has expired.')]);
    }
}
