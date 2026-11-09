<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Pulse\Facades\Pulse;
use Throwable;

class CheckHealth extends Command
{
    protected $signature = 'health:check';

    protected $description = 'Request the site\'s health endpoint and record the result for the monitoring page';

    public function handle(): int
    {
        $url = route('health');
        $started = hrtime(true);
        $httpStatus = null;

        try {
            $response = Http::timeout(10)
                ->withoutRedirecting()
                ->withHeaders(array_filter([
                    'CF-Access-Client-Id' => config('services.cloudflare_access.client_id'),
                    'CF-Access-Client-Secret' => config('services.cloudflare_access.client_secret'),
                ]))
                ->get($url);
            $httpStatus = $response->status();
            $error = $httpStatus < 400 ? null : 'HTTP '.$httpStatus;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $duration = (int) round((hrtime(true) - $started) / 1_000_000);
        $status = $error === null ? 'up' : 'down';

        Pulse::record('health_check', $status, $duration)->count()->avg();
        Pulse::set('health_check', 'latest', json_encode(['status' => $status, 'duration' => $duration, 'error' => $error, 'http_status' => $httpStatus]));

        $context = ['url' => $url, 'status' => $status, 'http_status' => $httpStatus, 'duration_ms' => $duration];

        if ($error !== null) {
            Pulse::set('health_check_failure', (string) now()->getTimestamp(), json_encode(['duration' => $duration, 'error' => $error, 'http_status' => $httpStatus]));
            Log::error('Health check failed', $context + ['error' => $error]);
            $this->error("{$url} is down: {$error}");

            return self::FAILURE;
        }

        Log::info('Health check passed', $context);
        $this->info("{$url} is up (HTTP {$httpStatus}, {$duration} ms)");

        return self::SUCCESS;
    }
}
