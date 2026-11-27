<?php

use App\Enums\UserRole;
use App\Features\HealthAndSecurityChecks;
use App\Support\SystemChecks;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Laravel\Pennant\Feature;

function health_checks_flag(bool $on): void
{
    config(['app.readererer_feature_flags' => $on ? [HealthAndSecurityChecks::class => ['enabled' => true]] : []]);
    Feature::flushCache();
}

test('admins see the health and security checks on the settings page while the flag is on', function () {
    health_checks_flag(true);
    config(['app.debug' => true]);

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('settings.edit'))
        ->assertOk()
        ->assertSee('Health and security checks')
        ->assertSee('allow_url_fopen is off')
        ->assertSee('allow_url_include is off')
        ->assertSee('Set APP_DEBUG=false');
});

test('a passing check hides its fix', function () {
    health_checks_flag(true);
    config(['app.debug' => false]);

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('settings.edit'))
        ->assertSee('Debug mode is off')
        ->assertDontSee('Set APP_DEBUG=false');
});

test('moderators and admins without the flag do not see the checks', function (UserRole $role, bool $flag) {
    health_checks_flag($flag);

    $this->actingAs(make_user($role))
        ->get(route('settings.edit'))
        ->assertOk()
        ->assertDontSee('Health and security checks');
})->with([
    'moderator' => [UserRole::Moderator, true],
    'admin without the flag' => [UserRole::Admin, false],
]);

test('the scheduler check passes only after the scheduler has run recently', function () {
    $scheduler = fn () => collect(SystemChecks::all())->firstWhere(0, 'The scheduler is running')[1];

    Cache::forget(SystemChecks::SCHEDULER_HEARTBEAT);
    expect($scheduler())->toBeFalse();

    Artisan::call('schedule:test', ['--name' => 'scheduler-heartbeat']);
    expect($scheduler())->toBeTrue();

    $this->travel(10)->minutes();
    expect($scheduler())->toBeFalse();
});

test('the Redis check shows how Redis is reached, only while the site uses Redis', function (array $redis, string $label, bool $passed) {
    $check = fn () => collect(SystemChecks::all())->first(fn ($check) => str_contains($check[0], 'Redis'));

    config(['database.redis.default' => $redis + ['port' => 6379]]);
    expect($check())->toBeNull();

    config(['queue.default' => 'redis']);
    expect($check())->toMatchArray([$label, $passed]);
})->with([
    'socket' => [['host' => '/var/run/redis.sock'], 'Redis connects through a Unix socket', true],
    'unix scheme' => [['scheme' => 'unix', 'path' => '/var/run/redis.sock', 'host' => null], 'Redis connects through a Unix socket', true],
    'loopback' => [['host' => '127.0.0.1'], 'Redis connects over TCP to this server (127.0.0.1:6379)', true],
    'another host' => [['host' => 'redis.example.com'], 'Redis connects over TCP to redis.example.com:6379', false],
    'url' => [['url' => 'tcp://redis.example.com:6380', 'host' => null], 'Redis connects over TCP to redis.example.com:6380', false],
]);

test('a SQLite database passes the database check', function () {
    config(['database.default' => 'sqlite']);

    expect(collect(SystemChecks::all())->firstWhere(0, 'The database is a SQLite file on this server'))->not->toBeNull();
});
