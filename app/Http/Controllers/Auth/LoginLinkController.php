<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\LoginLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Reuses the password reset tokens, so a link is single-use, expires and is throttled like a reset link.
        $status = Password::sendResetLink(
            $this->credentials($request->email),
            fn (User $user, string $token) => $user->notify(new LoginLink($token))
        );

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
        if (! is_string($email)) {
            return null;
        }

        $user = Password::getUser($this->credentials($email));

        return $user && Password::tokenExists($user, $token) ? $user : null;
    }

    /** @return array<int|string, mixed> */
    private function credentials(string $email): array
    {
        return ['email' => $email, fn ($query) => $query->where('role', '!=', UserRole::Ensemble)];
    }

    private function invalid(): RedirectResponse
    {
        return redirect()->route('login-link.request')->withErrors(['email' => __('This login link is invalid or has expired.')]);
    }
}
