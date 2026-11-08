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

        try {
            $response = Http::timeout(10)
                ->withoutRedirecting()
                ->withHeaders(array_filter([
                    'CF-Access-Client-Id' => config('services.cloudflare_access.client_id'),
                    'CF-Access-Client-Secret' => config('services.cloudflare_access.client_secret'),
                ]))
                ->get($url);
            $error = $response->status() === 200 ? null : 'HTTP '.$response->status();
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $duration = (int) round((hrtime(true) - $started) / 1_000_000);
        $status = $error === null ? 'up' : 'down';

        Pulse::record('health_check', $status, $duration)->count()->avg();
        Pulse::set('health_check', 'latest', json_encode(['status' => $status, 'duration' => $duration, 'error' => $error]));

        if ($error !== null) {
            Log::error('Health check failed', ['url' => $url, 'error' => $error, 'duration_ms' => $duration]);
            $this->error("{$url} is down: {$error}");

            return self::FAILURE;
        }

        $this->info("{$url} is up ({$duration} ms)");

        return self::SUCCESS;
    }
}
