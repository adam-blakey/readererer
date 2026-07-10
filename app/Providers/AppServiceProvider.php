<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Debugbar', Debugbar::class);

        $this->app->register(FakerServiceProvider::class);
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
                ->subject(config('app.name').': '.__('Reset Password Request'))
                ->greeting(__('Hello!'))
                ->line(__('You recently requested to reset a password for your').' '.config('app.name').' '.__('account. Use the button below to reset it. This message will expire in :count minutes.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
                ->action(__('Reset password'), $url)
                ->line(__('If you did not request a password reset, then please ignore this message.'))
                ->salutation(__('Regards,')."\r\n\r\n".config('app.name').' Team');
        });

        Gate::define('view.dashboard', function (User $user) {
            return true;
        });

        Gate::define('view.notifications', function (User $user) {
            return $user->role->value >= UserRole::Moderator->value;
        });

        Model::preventSilentlyDiscardingAttributes(config('app.env') == 'local');
    }
}
