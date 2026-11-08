<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Laravel\Pulse\Facades\Pulse;

class MonitoringController extends Controller
{
    public const PERIODS = ['1_hour' => 1, '6_hours' => 6, '24_hours' => 24, '7_days' => 168];

    public const CHECK_MINUTES = 5;

    public const LOG_LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    public function index(Request $request)
    {
        $period = array_key_exists((string) $request->query('period'), self::PERIODS) ? $request->query('period') : '24_hours';
        $interval = CarbonInterval::hours(self::PERIODS[$period]);
        $now = CarbonImmutable::now();

        $checks = DB::connection(config('pulse.storage.database.connection'))
            ->table('pulse_entries')
            ->where('type', 'health_check')
            ->where('timestamp', '>=', $now->subHours(max(self::PERIODS))->getTimestamp())
            ->orderBy('timestamp')
            ->get(['timestamp', 'key', 'value']);

        $availability = collect(self::PERIODS)->map(fn (int $hours) => $this->summarise(
            $checks->where('timestamp', '>=', $now->subHours($hours)->getTimestamp())
        ));

        $latest = Pulse::values('health_check', ['latest'])->first();

        $users = Pulse::aggregate('user_request', 'count', $interval, limit: 10);
        $slow_users = Pulse::aggregate('slow_user_request', 'count', $interval)->pluck('count', 'key');
        $user_models = User::withTrashed()->findMany($users->pluck('key'))->keyBy('id');

        $cache = Pulse::aggregateTotal(['cache_hit', 'cache_miss'], 'count', $interval);

        return view('settings.monitoring', [
            'page_name' => __('Monitoring'),
            'period' => $period,
            'latest' => $latest ? (object) [...json_decode($latest->value, true), 'at' => $this->at((int) $latest->timestamp)] : null,
            'summary' => $availability[$period],
            'availability' => $availability,
            'buckets' => $this->buckets($checks, $now, self::PERIODS[$period]),
            'failures' => Pulse::values('health_check_failure')
                ->sortByDesc('timestamp')
                ->take(10)
                ->map(fn ($row) => (object) [...json_decode($row->value, true), 'at' => $this->at((int) $row->timestamp)]),
            'requests' => (int) Pulse::aggregateTotal('user_request', 'count', $interval),
            'exception_count' => (int) Pulse::aggregateTotal('exception', 'count', $interval),
            'cache' => (object) ['hits' => (int) ($cache['cache_hit'] ?? 0), 'misses' => (int) ($cache['cache_miss'] ?? 0)],
            'exceptions' => $this->decoded('exception', $interval, ['class', 'location']),
            'slow_requests' => $this->decoded('slow_request', $interval, ['method', 'uri', 'action']),
            'slow_queries' => $this->decoded('slow_query', $interval, ['sql', 'location']),
            'slow_outgoing_requests' => $this->decoded('slow_outgoing_request', $interval, ['method', 'uri']),
            'active_users' => $users
                ->map(fn ($row) => (object) [
                    'user' => $user_models->get($row->key),
                    'count' => (int) $row->count,
                    'slow' => (int) ($slow_users[$row->key] ?? 0),
                ])
                ->filter(fn ($row) => $row->user),
        ]);
    }

    public function logs(Request $request)
    {
        $level = in_array($request->query('level'), self::LOG_LEVELS, true) ? $request->query('level') : null;
        $file = collect(File::glob(storage_path('logs/laravel*.log')))
            ->sortByDesc(fn (string $path) => File::lastModified($path))
            ->first();

        $entries = $file ? $this->readLog($file) : collect();

        if ($level) {
            $minimum = array_search($level, self::LOG_LEVELS, true);
            $entries = $entries->filter(fn ($entry) => array_search($entry->level, self::LOG_LEVELS, true) >= $minimum);
        }

        return view('settings.logs', [
            'page_name' => __('Application log'),
            'file' => $file ? basename($file) : null,
            'level' => $level,
            'entries' => $entries->take(100),
        ]);
    }

    private function readLog(string $path, int $bytes = 512 * 1024): Collection
    {
        $size = filesize($path);
        $handle = fopen($path, 'r');
        fseek($handle, max(0, $size - $bytes));
        $lines = explode("\n", (string) stream_get_contents($handle));
        fclose($handle);

        if ($size > $bytes) {
            array_shift($lines);
        }

        $entries = [];
        foreach ($lines as $line) {
            $json = json_decode($line, true);

            if (is_array($json) && isset($json['message'], $json['level_name'], $json['datetime'])) {
                $details = array_filter(['context' => $json['context'] ?? [], 'extra' => $json['extra'] ?? []]);
                $entries[] = (object) [
                    'at' => CarbonImmutable::parse($json['datetime']),
                    'level' => strtolower($json['level_name']),
                    'message' => $json['message'],
                    'details' => $details ? json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '',
                ];
            } elseif (preg_match('/^\[([^\]]+)\] \w+\.(\w+): (.*)$/', $line, $match)) {
                $entries[] = (object) ['at' => CarbonImmutable::parse($match[1]), 'level' => strtolower($match[2]), 'message' => $match[3], 'details' => ''];
            } elseif ($entries && trim($line) !== '') {
                end($entries)->details = ltrim(end($entries)->details."\n".$line);
            }
        }

        return collect($entries)->reverse()->values();
    }

    private function summarise(Collection $checks): object
    {
        $up = $checks->where('key', 'up');

        return (object) [
            'checks' => $checks->count(),
            'failed' => $checks->count() - $up->count(),
            'uptime' => $checks->isEmpty() ? null : round(100 * $up->count() / $checks->count(), 2),
            'average' => $up->isEmpty() ? null : (int) round($up->avg('value')),
        ];
    }

    private function buckets(Collection $checks, CarbonImmutable $now, int $hours): Collection
    {
        $count = min(60, intdiv($hours * 60, self::CHECK_MINUTES));
        $size = intdiv($hours * 3600, $count);
        $start = $now->getTimestamp() + 1 - $hours * 3600;

        return collect(range(0, $count - 1))->map(function (int $i) use ($checks, $start, $size) {
            $from = $start + $i * $size;
            $summary = $this->summarise($checks->where('timestamp', '>=', $from)->where('timestamp', '<', $from + $size));

            return (object) [
                ...(array) $summary,
                'from' => $this->at($from),
                'status' => match (true) {
                    $summary->checks === 0 => 'none',
                    $summary->failed === 0 => 'up',
                    $summary->failed === $summary->checks => 'down',
                    default => 'degraded',
                },
            ];
        });
    }

    private function at(int $timestamp): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestamp($timestamp, date_default_timezone_get());
    }

    private function decoded(string $type, CarbonInterval $interval, array $fields): Collection
    {
        return Pulse::aggregate($type, ['max', 'count'], $interval, 'count', limit: 10)
            ->map(fn ($row) => (object) [
                ...array_combine($fields, json_decode($row->key, true)),
                'max' => (int) $row->max,
                'latest' => $type === 'exception' ? $this->at((int) $row->max) : null,
                'count' => (int) $row->count,
            ]);
    }
}
