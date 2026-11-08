<?php

namespace App\Providers;

use App\Contracts\EmailSender;
use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use App\Services\EmailSenders\LogEmailSender;
use App\Services\EmailSenders\SmtpEmailSender;
use App\Translation\TerminologyLoader;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Debugbar', Debugbar::class);

        $this->app->register(FakerServiceProvider::class);

        $this->app->bind(EmailSender::class, fn () => config('app.readererer_log_emails') ? new LogEmailSender : new SmtpEmailSender);

        // The monitoring page replaces Pulse's own dashboard, whose inline scripts the Content Security Policy blocks.
        Pulse::ignoreRoutes();

        $this->app->extend('translation.loader', fn (Loader $loader) => new TerminologyLoader($loader));
    }

    public function boot(): void
    {
        Setting::applyToConfig();

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Context::add('site', parse_url((string) config('app.url'), PHP_URL_HOST));

        Paginator::defaultView('pagination::default');

        Gate::define('view.dashboard', function (User $user) {
            return true;
        });

        Gate::define('view.settings', function (User $user) {
            return $user->role->value >= UserRole::Moderator->value;
        });

        Gate::define('edit.settings', function (User $user) {
            return $user->role === UserRole::Admin;
        });

        Gate::define('view.notifications', function (User $user) {
            return $user->role->value >= UserRole::Moderator->value;
        });

        Model::preventSilentlyDiscardingAttributes(config('app.env') == 'local');
    }
}
