<?php

use App\Enums\UserRole;
use Illuminate\Support\Facades\Http;
use Laravel\Pulse\Facades\Pulse;

beforeEach(function () {
    Pulse::startRecording();
});

test('only admins can see the monitoring page', function (UserRole $role, int $status) {
    $this->actingAs(make_user($role))
        ->get(route('settings.monitoring'))
        ->assertStatus($status);
})->with([
    'moderator' => [UserRole::Moderator, 403],
    'admin' => [UserRole::Admin, 200],
]);

test('a guest is sent to log in', function () {
    $this->get(route('settings.monitoring'))->assertRedirect();
});

test('the page explains that the health check has not run', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('settings.monitoring'))
        ->assertOk()
        ->assertSee('The health check has not run yet.')
        ->assertSee('No exceptions in this period.');
});

test('the page shows what Pulse recorded', function () {
    $user = make_user(UserRole::Admin, ['first_name' => 'Alex', 'last_name' => 'Admin']);

    Pulse::record('exception', json_encode(['App\Exceptions\BrokenThing', 'app/Broken.php:12']), now()->timestamp)->max()->count();
    Pulse::record('slow_query', json_encode(['select * from "terms"', 'app/Http/Controllers/TermController.php:20']), 1500)->max()->count();
    Pulse::record('user_request', (string) $user->id)->count();
    Pulse::record('health_check', 'up', 120)->count()->avg();
    Pulse::record('health_check', 'down', 80)->count()->avg();
    Pulse::set('health_check', 'latest', json_encode(['status' => 'down', 'duration' => 80, 'error' => 'HTTP 503']));
    Pulse::ingest();

    $this->actingAs($user)
        ->get(route('settings.monitoring', ['period' => '1_hour']))
        ->assertOk()
        ->assertSee('App\Exceptions\BrokenThing')
        ->assertSee('select * from &quot;terms&quot;', false)
        ->assertSee('1,500 ms')
        ->assertSee('Alex Admin')
        ->assertSee('50%')
        ->assertSee('120 ms')
        ->assertSee('HTTP 503')
        ->assertDontSee('Check that the scheduler');
});

test('the page warns when the health check has stopped running', function () {
    Pulse::set('health_check', 'latest', json_encode(['status' => 'up', 'duration' => 90, 'error' => null]), now()->subHour());
    Pulse::ingest();

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('settings.monitoring'))
        ->assertOk()
        ->assertSee('Check that the scheduler', false);
});

test('the health check command records the site as up', function () {
    Http::fake([route('health') => Http::response(['status' => 'ok'])]);

    $this->artisan('health:check')->assertSuccessful();
    Pulse::ingest();

    expect(json_decode(Pulse::values('health_check', ['latest'])->first()->value, true))
        ->toMatchArray(['status' => 'up', 'error' => null]);
});

test('the health check command logs a failure', function () {
    Http::fake([route('health') => Http::response(['status' => 'error'], 503)]);
    Log::spy();

    $this->artisan('health:check')->assertFailed();
    Pulse::ingest();

    Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context) => $context['error'] === 'HTTP 503');
    expect(json_decode(Pulse::values('health_check', ['latest'])->first()->value, true))
        ->toMatchArray(['status' => 'down', 'error' => 'HTTP 503']);
});

test('the health check command sends the Cloudflare Access service token', function () {
    config(['services.cloudflare_access.client_id' => 'id', 'services.cloudflare_access.client_secret' => 'secret']);
    Http::fake();

    $this->artisan('health:check')->assertSuccessful();

    Http::assertSent(fn ($request) => $request->hasHeader('CF-Access-Client-Id', 'id') && $request->hasHeader('CF-Access-Client-Secret', 'secret'));
});
