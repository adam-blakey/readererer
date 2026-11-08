<?php

use Illuminate\Support\Facades\Context;

test('each request gets an id and its route in the log context', function () {
    $user = make_user();

    $response = $this->actingAs($user)->get(route('dashboard'));

    expect($response->headers->get('X-Request-Id'))->toBe(Context::get('request_id'))
        ->and(Context::get('route'))->toBe('dashboard')
        ->and(Context::get('uri'))->toBe('dashboard')
        ->and(Context::get('user_id'))->toBe($user->id);
});

test('nightwatch stays off without a token but provides its log channel', function () {
    expect(config('nightwatch.enabled'))->toBeFalse()
        ->and(config('logging.channels.nightwatch.driver'))->toBe('custom');
});
