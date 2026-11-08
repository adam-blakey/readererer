<?php

use App\Console\Commands\SendAnalytics;
use App\Enums\UserRole;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

function record_event(array $overrides = []): void
{
    DB::table('analytics_events')->insert(array_merge([
        'method' => 'GET',
        'route' => 'terms.show',
        'status' => 200,
        'duration_ms' => 42,
        'created_at' => now(),
    ], $overrides));
}

test('nothing is recorded until analytics are switched on', function () {
    $this->actingAs(make_user(UserRole::Admin))->get(route('dashboard'))->assertOk();

    expect(DB::table('analytics_events')->count())->toBe(0);
});

test('a request is recorded by route name without the URL or the user', function () {
    config(['app.readererer_analytics' => true]);
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('users.show', $admin).'?secret=1')->assertOk();

    $event = DB::table('analytics_events')->sole();
    expect($event->method)->toBe('GET')
        ->and($event->route)->toBe('users.show')
        ->and((int) $event->status)->toBe(200)
        ->and(json_encode($event))->not->toContain($admin->email)->not->toContain('secret');
});

test('the batch is sent as OTLP logs and removed once accepted', function () {
    config(['app.readererer_analytics' => true]);
    Http::fake([SendAnalytics::URL => Http::response()]);
    record_event();
    record_event(['route' => 'dashboard', 'status' => 500]);

    $this->artisan('analytics:send')->assertSuccessful();

    Http::assertSent(function (Request $request) {
        $records = $request['resourceLogs'][0]['scopeLogs'][0]['logRecords'];

        return $request->url() === SendAnalytics::URL
            && count($records) === 2
            && in_array(['key' => 'http.response.status_code', 'value' => ['intValue' => 500]], $records[1]['attributes'], true);
    });
    expect(DB::table('analytics_events')->count())->toBe(0);
});

test('failed sends are kept for retrying and dropped after the last attempt', function () {
    config(['app.readererer_analytics' => true]);
    Http::fake([SendAnalytics::URL => Http::failedConnection()]);
    record_event();

    foreach (range(1, SendAnalytics::MAX_ATTEMPTS - 1) as $attempt) {
        $this->artisan('analytics:send')->assertSuccessful();
    }
    expect(DB::table('analytics_events')->value('attempts'))->toBe(SendAnalytics::MAX_ATTEMPTS - 1);

    Http::fake([SendAnalytics::URL => Http::response(status: 503)]);
    $this->artisan('analytics:send')->assertSuccessful();

    expect(DB::table('analytics_events')->count())->toBe(0);
});

test('switching analytics off discards what was recorded without sending it', function () {
    Http::fake();
    record_event();

    $this->artisan('analytics:send')->assertSuccessful();

    Http::assertNothingSent();
    expect(DB::table('analytics_events')->count())->toBe(0);
});
