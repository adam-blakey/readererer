<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemChecks
{
    public const SCHEDULER_HEARTBEAT = 'scheduler_heartbeat';

    public static function all(): array
    {
        $heartbeat = Cache::get(self::SCHEDULER_HEARTBEAT);

        return array_values(array_filter([
            [__('allow_url_fopen is off'), ! self::iniEnabled('allow_url_fopen'), __('Set allow_url_fopen = Off in php.ini, so PHP\'s file functions cannot open remote URLs.')],
            [__('allow_url_include is off'), ! self::iniEnabled('allow_url_include'), __('Set allow_url_include = Off in php.ini, so PHP cannot include code from remote URLs.')],
            [__('Debug mode is off'), ! config('app.debug'), __('Set APP_DEBUG=false in the site\'s .env, as error pages in debug mode show the configuration and secrets.')],
            [__('PHP errors are not displayed'), ! self::iniEnabled('display_errors'), __('Set display_errors = Off in php.ini, so errors are logged rather than shown to visitors.')],
            [__('PHP version is hidden'), ! self::iniEnabled('expose_php'), __('Set expose_php = Off in php.ini, so responses do not advertise the PHP version.')],
            [__('The site address uses HTTPS'), str_starts_with((string) config('app.url'), 'https://'), __('Set APP_URL in the site\'s .env to the https:// address, which links in emails use.')],
            [__('Session cookies are only sent over HTTPS'), (bool) config('session.secure'), __('Set SESSION_SECURE_COOKIE=true in the site\'s .env.')],
            [__('Uploaded images can be served'), file_exists(public_path('storage')), __('Run php artisan storage:link.')],
            [__('Storage folders are writable'), is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')), __('Let the web server write to storage and bootstrap/cache.')],
            [__('The scheduler is running'), $heartbeat && Carbon::parse($heartbeat)->gt(now()->subMinutes(5)), __('Add a cron job running php artisan schedule:run every minute, which sends reminders and runs health checks.')],
            self::databaseCheck(),
            self::redisCheck(),
        ]));
    }

    private static function databaseCheck(): array
    {
        $config = DB::connection()->getConfig();

        if ($config['driver'] === 'sqlite') {
            return [__('The database is a SQLite file on this server'), true, ''];
        }

        return self::connectionCheck(__('The database'), $config, 'DB_SOCKET');
    }

    private static function redisCheck(): ?array
    {
        if (! in_array('redis', [config('cache.default'), config('session.driver'), config('queue.default')], true)) {
            return null;
        }

        return self::connectionCheck(__('Redis'), (new ConfigurationUrlParser)->parseConfiguration(config('database.redis.default')), 'REDIS_HOST');
    }

    private static function connectionCheck(string $service, array $config, string $socketVariable): array
    {
        $host = (string) ($config['host'] ?? '');

        if (filled($config['unix_socket'] ?? null) || ($config['scheme'] ?? null) === 'unix' || str_starts_with($host, '/') || str_starts_with($host, 'unix:')) {
            return [__(':service connects through a Unix socket', ['service' => $service]), true, ''];
        }

        $address = $host.(isset($config['port']) ? ':'.$config['port'] : '');

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return [__(':service connects over TCP to this server (:address)', ['service' => $service, 'address' => $address]), true, ''];
        }

        return [
            __(':service connects over TCP to :address', ['service' => $service, 'address' => $address]),
            false,
            __('Connect through a Unix socket (:variable) or to this server where you can. Otherwise make sure :address only accepts encrypted connections from this server.', ['variable' => $socketVariable, 'address' => $address]),
        ];
    }

    private static function iniEnabled(string $key): bool
    {
        return ! in_array(strtolower((string) ini_get($key)), ['', '0', 'off', 'no', 'false', 'stderr'], true);
    }
}
