<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Laravel\Pulse\Facades\Pulse;

class MonitoringController extends Controller
{
    public const PERIODS = ['1_hour' => 1, '6_hours' => 6, '24_hours' => 24, '7_days' => 168];

    public function __invoke(Request $request)
    {
        $period = array_key_exists((string) $request->query('period'), self::PERIODS) ? $request->query('period') : '24_hours';
        $interval = CarbonInterval::hours(self::PERIODS[$period]);

        $health = Pulse::aggregate('health_check', ['count', 'avg'], $interval)->keyBy('key');
        $checks = (int) $health->sum('count');
        $latest = Pulse::values('health_check', ['latest'])->first();

        $active_users = Pulse::aggregate('user_request', 'count', $interval, limit: 10);
        $users = User::withTrashed()->findMany($active_users->pluck('key'))->keyBy('id');

        return view('settings.monitoring', [
            'page_name' => __('Monitoring'),
            'period' => $period,
            'health' => [
                'checks' => $checks,
                'uptime' => $checks > 0 ? round(100 * ($health['up']->count ?? 0) / $checks, 2) : null,
                'average' => isset($health['up']) ? (int) round($health['up']->avg) : null,
                'latest' => $latest ? (object) [...json_decode($latest->value, true), 'at' => CarbonImmutable::createFromTimestamp($latest->timestamp)] : null,
            ],
            'exceptions' => $this->decoded('exception', $interval, ['class', 'location']),
            'slow_requests' => $this->decoded('slow_request', $interval, ['method', 'uri', 'action']),
            'slow_queries' => $this->decoded('slow_query', $interval, ['sql', 'location']),
            'slow_outgoing_requests' => $this->decoded('slow_outgoing_request', $interval, ['method', 'uri']),
            'active_users' => $active_users
                ->map(fn ($row) => (object) ['user' => $users->get($row->key), 'count' => (int) $row->count])
                ->filter(fn ($row) => $row->user),
        ]);
    }

    private function decoded(string $type, CarbonInterval $interval, array $fields)
    {
        return Pulse::aggregate($type, ['max', 'count'], $interval, 'count', limit: 10)
            ->map(fn ($row) => (object) [
                ...array_combine($fields, json_decode($row->key, true)),
                'max' => (int) $row->max,
                'count' => (int) $row->count,
            ]);
    }
}
