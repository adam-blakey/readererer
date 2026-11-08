<?php

use App\Logging\HttpLogHandler;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Http;
use Monolog\Logger;

test('the http handler sends buffered entries in one request', function () {
    Http::fake();
    $handler = new HttpLogHandler('https://logs.example.test', 'source-token');
    $logger = new Logger('test', [$handler]);

    $logger->info('First', ['term' => 3]);
    $logger->error('Second');
    $handler->close();

    Http::assertSentCount(1);
    Http::assertSent(function ($request) {
        $events = $request->data();

        return $request->url() === 'https://logs.example.test'
            && $request->hasHeader('Authorization', 'Bearer source-token')
            && count($events) === 2
            && $events[0]['message'] === 'First'
            && $events[0]['context'] === ['term' => 3]
            && $events[1]['level_name'] === 'ERROR'
            && isset($events[0]['dt']);
    });
});

test('the http handler sends nothing without a url', function () {
    Http::fake();
    $handler = new HttpLogHandler(null);

    (new Logger('test', [$handler]))->error('Dropped');
    $handler->close();

    Http::assertNothingSent();
});

test('the http handler does not throw when the service fails', function () {
    Http::fake(['*' => Http::response('', 500)]);
    $handler = new HttpLogHandler('https://logs.example.test');

    (new Logger('test', [$handler]))->error('Lost');

    expect(fn () => $handler->close())->not->toThrow(Throwable::class);
});

test('each request gets an id and its route in the log context', function () {
    $user = make_user();

    $response = $this->actingAs($user)->get(route('dashboard'));

    expect($response->headers->get('X-Request-Id'))->toBe(Context::get('request_id'))
        ->and(Context::get('route'))->toBe('dashboard')
        ->and(Context::get('uri'))->toBe('dashboard')
        ->and(Context::get('user_id'))->toBe($user->id);
});
