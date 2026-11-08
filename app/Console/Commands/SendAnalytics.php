<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendAnalytics extends Command
{
    public const URL = 'https://logs.blakey.family/v1/logs';

    public const MAX_ATTEMPTS = 10;

    public const BATCH_SIZE = 1000;

    protected $signature = 'analytics:send';

    protected $description = 'Send the recorded anonymous analytics to logs.blakey.family as OpenTelemetry (OTLP/HTTP JSON) logs';

    public function handle(): int
    {
        $events = DB::table('analytics_events');

        if (! config('app.readererer_analytics')) {
            $events->delete();

            return self::SUCCESS;
        }

        $batch = (clone $events)->orderBy('id')->limit(self::BATCH_SIZE)->get();

        if ($batch->isEmpty()) {
            return self::SUCCESS;
        }

        try {
            $sent = Http::timeout(10)->post(self::URL, $this->payload($batch))->successful();
        } catch (Throwable) {
            $sent = false;
        }

        $ids = $batch->pluck('id');

        if ($sent) {
            (clone $events)->whereIn('id', $ids)->delete();
        } else {
            (clone $events)->whereIn('id', $ids)->increment('attempts');
            (clone $events)->where('attempts', '>=', self::MAX_ATTEMPTS)->delete();
        }

        return self::SUCCESS;
    }

    private function payload(Collection $batch): array
    {
        return ['resourceLogs' => [[
            'resource' => ['attributes' => $this->attributes([
                'service.name' => 'readererer',
                'service.version' => config('app.version'),
            ])],
            'scopeLogs' => [[
                'scope' => ['name' => 'readererer.analytics'],
                'logRecords' => $batch->map(fn ($event) => [
                    'timeUnixNano' => strtotime($event->created_at).'000000000',
                    'severityNumber' => 9,
                    'severityText' => 'INFO',
                    'body' => ['stringValue' => 'request'],
                    'attributes' => $this->attributes([
                        'http.request.method' => $event->method,
                        'http.route' => $event->route,
                        'http.response.status_code' => (int) $event->status,
                        'duration_ms' => $event->duration_ms === null ? null : (int) $event->duration_ms,
                    ]),
                ])->all(),
            ]],
        ]]];
    }

    private function attributes(array $values): array
    {
        return collect($values)
            ->reject(fn ($value) => $value === null)
            ->map(fn ($value, $key) => ['key' => $key, 'value' => is_int($value) ? ['intValue' => $value] : ['stringValue' => (string) $value]])
            ->values()
            ->all();
    }
}
