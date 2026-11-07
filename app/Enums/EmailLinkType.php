<?php

namespace App\Enums;

use App\Models\User;
use App\Notifications\EmailLink;
use Illuminate\Support\Facades\Password;

enum EmailLinkType
{
    case ResetPassword;
    case LoginLink;

    public function send(string $email): string
    {
        return Password::sendResetLink(
            $this->credentials($email),
            fn (User $user, string $token) => $user->notify(new EmailLink($this, $token))
        );
    }

    public function user(string $email, string $token): ?User
    {
        $user = Password::getUser($this->credentials($email));

        return $user && Password::tokenExists($user, $token) ? $user : null;
    }

    public function url(string $token, string $email): string
    {
        return route(match ($this) {
            self::ResetPassword => 'password.reset',
            self::LoginLink => 'login-link.show',
        }, ['token' => $token, 'email' => $email]);
    }

    /** @return array<int|string, mixed> */
    private function credentials(string $email): array
    {
        return match ($this) {
            self::ResetPassword => ['email' => $email],
            self::LoginLink => ['email' => $email, fn ($query) => $query->where('role', '!=', UserRole::Ensemble)],
        };
    }
}
