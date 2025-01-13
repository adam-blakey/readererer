<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Debugbar', \Barryvdh\Debugbar\Facades\Debugbar::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('pagination::default');

        ResetPassword::toMailUsing(function (User $user, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject(config('app.name') . ': ' . __('Reset Password Request'))
                ->greeting(__('Hello!'))
                ->line(__('You recently requested to reset a password for your') . ' ' . config('app.name') . ' ' . __('account. Use the button below to reset it. This message will expire in :count minutes.', ['count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire')]))
                ->action(__('Reset password'), $url)
                ->line(__('If you did not request a password reset, then please ignore this message.'))
                ->salutation(__('Regards,') . "\r\n\r\n" . config('app.name') . " Team");
        });

        Gate::define('view.dashboard', function (User $user) {
            return true;
        });
    }
}