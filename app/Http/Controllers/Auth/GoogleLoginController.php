<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as GoogleUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleLoginController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        abort_unless(google_login_enabled(), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(google_login_enabled(), 404);

        try {
            $google = Socialite::driver('google')->user();
        } catch (InvalidStateException|GuzzleException) {
            return $this->fail(__('Logging in with Google failed. Please try again.'));
        }

        $email = Str::lower((string) $google->getEmail());

        if ($email === '' || ! ($google->getRaw()['email_verified'] ?? false)) {
            return $this->fail(__('Your Google account has no verified email address.'));
        }

        $users = User::whereRaw('lower(email) = ?', [$email])->limit(2)->get();

        if ($users->count() > 1) {
            return $this->fail(__('More than one account uses :email, so log in with your username instead.', ['email' => $email]));
        }

        if ($users->first()?->role === UserRole::Ensemble) {
            return $this->fail(__('Shared ensemble logins cannot log in with Google.'));
        }

        $user = $users->first() ?? $this->register($google, $email);

        if (! $user) {
            return $this->fail(__('No account uses :email.', ['email' => $email]));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function register(GoogleUser $google, string $email): ?User
    {
        $domain = config('app.readererer_google_register_domain');
        $role = config('app.readererer_google_register_role');

        if (blank($domain) || blank($role) || Str::after($email, '@') !== Str::lower($domain)) {
            return null;
        }

        $first_name = $google->getRaw()['given_name'] ?? $google->getName() ?? Str::before($email, '@');
        $last_name = $google->getRaw()['family_name'] ?? '';

        // Someone else may take the generated username between the check and the insert.
        return retry(3, fn () => User::create([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'username' => User::generateUniqueUsername($first_name, $last_name),
            'email' => $email,
            'password' => Str::password(16),
            'role' => UserRole::from((int) $role),
        ]), when: fn (Throwable $exception) => $exception instanceof UniqueConstraintViolationException);
    }

    private function fail(string $message): RedirectResponse
    {
        return to_route('login')->withErrors(['username' => $message]);
    }
}
