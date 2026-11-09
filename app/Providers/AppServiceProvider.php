<?php

namespace App\Providers;

use App\Contracts\EmailSender;
use App\Enums\UserRole;
use App\Logging\NightwatchRedaction;
use App\Models\User;
use App\Services\EmailSenders\LogEmailSender;
use App\Services\EmailSenders\SmtpEmailSender;
use App\Settings\GeneralSettings;
use App\Translation\TerminologyLoader;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Nightwatch\Core as Nightwatch;
use Laravel\Pennant\Feature;
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
        GeneralSettings::applyToConfig();

        $nightwatch = $this->app->make(Nightwatch::class);

        if (! config('app.readererer_analytics')) {
            // Nightwatch reads its config before the settings are applied, so the opt-out samples nothing instead.
            $nightwatch->config['sampling'] = array_fill_keys(array_keys($nightwatch->config['sampling']), 0.0);
        }

        $nightwatch->user(NightwatchRedaction::user(...));
        $nightwatch->redactRequests(NightwatchRedaction::request(...));
        $nightwatch->redactOutgoingRequests(NightwatchRedaction::outgoingRequest(...));
        $nightwatch->redactCommands(NightwatchRedaction::command(...));
        $nightwatch->redactExceptions(NightwatchRedaction::exception(...));

        Event::listen(Login::class, fn (Login $event) => Log::info('User logged in', ['user_id' => $event->user->getAuthIdentifier(), 'remember' => $event->remember]));
        Event::listen(Failed::class, fn (Failed $event) => Log::warning('Login failed', ['user_id' => $event->user?->getAuthIdentifier()]));
        Event::listen(Lockout::class, fn () => Log::warning('Login locked out after too many attempts'));
        Event::listen(PasswordReset::class, fn (PasswordReset $event) => Log::info('Password reset', ['user_id' => $event->user->getAuthIdentifier()]));

        Feature::discover();

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

        Gate::define('send.notifications', function (User $user) {
            return $user->role === UserRole::Admin;
        });

        Model::preventSilentlyDiscardingAttributes(config('app.env') == 'local');
    }
}
