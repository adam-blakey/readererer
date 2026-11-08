<?php

use App\Logging\NightwatchRedaction;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use Laravel\Nightwatch\Records\Command;
use Laravel\Nightwatch\Records\Exception;
use Laravel\Nightwatch\Records\OutgoingRequest;
use Laravel\Nightwatch\Records\Request;
use Monolog\Handler\GroupHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger as Monolog;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;

function nightwatch_request(string $url, string $routePath): Request
{
    return new Request(
        method: 'GET', url: $url, routeName: '', routeMethods: ['GET'], routeDomain: '', routePath: $routePath, routeAction: '',
        ip: '203.0.113.7', duration: 1, statusCode: 200, requestSize: 0, responseSize: 0,
        headers: new HeaderBag(['X-Forwarded-For' => '203.0.113.7', 'Referer' => 'https://site.test/login-link/secret']),
        payload: new InputBag(['email' => 'alice@example.com']),
        files: new FileBag,
    );
}

test('email addresses and query bindings are removed from text', function () {
    expect(redact_personal_data('Could not send to alice@example.com.'))->toBe('Could not send to [email].')
        ->and(redact_personal_data('SQLSTATE[23000]: UNIQUE constraint failed: users.email (Connection: sqlite, Database: db, SQL: insert into users (email) values (bob@example.com))'))
        ->toBe('SQLSTATE[23000]: UNIQUE constraint failed: users.email');
});

test('requests are sent by route pattern without the ip, headers or payload', function () {
    $request = nightwatch_request('https://site.test/login-link/secret-token?email=alice@example.com', '/login-link/{token}');

    NightwatchRedaction::request($request);

    expect($request->url)->toBe('https://site.test/login-link/{token}')
        ->and($request->ip)->toBe('')
        ->and($request->headers->all())->toBe([])
        ->and($request->payload->all())->toBe([]);
});

test('requests with no route lose their query string and any email address', function () {
    $request = nightwatch_request('https://site.test/missing/alice@example.com?token=secret', '');

    NightwatchRedaction::request($request);

    expect($request->url)->toBe('https://site.test/missing/[email]');
});

test('outgoing requests, commands and exceptions are redacted', function () {
    $outgoing = new OutgoingRequest(method: 'GET', url: 'https://nominatim.example/search?q=1+High+Street', duration: 1, requestSize: 0, responseSize: 0, statusCode: 200);
    $command = new Command(class: 'App\\Console\\Commands\\CreateUser', name: 'user:create', command: 'user:create Alice alice@example.com', exitCode: 0, duration: 1);
    $exception = new Exception(class: RuntimeException::class, message: 'No account for alice@example.com', code: 0, file: 'a.php', line: 1, handled: false);

    NightwatchRedaction::outgoingRequest($outgoing);
    NightwatchRedaction::command($command);
    NightwatchRedaction::exception($exception);

    expect($outgoing->url)->toBe('https://nominatim.example/search')
        ->and($command->command)->toBe('user:create')
        ->and($exception->message)->toBe('No account for [email]');
});

test('users are sent to nightwatch by id only', function () {
    $user = make_user();

    expect(NightwatchRedaction::user($user))->toBe(['id' => $user->id]);
});

test('the nightwatch log channel redacts its entries', function () {
    expect(Log::channel('nightwatch')->getHandlers()[0])->toBeInstanceOf(GroupHandler::class);

    $handler = new TestHandler;
    $logger = new Logger(new Monolog('test', [$handler]));
    (new NightwatchRedaction)($logger);

    $logger->warning('Failed to send to alice@example.com', [
        'ip' => '203.0.113.7',
        'error' => 'Mailbox bob@example.com unavailable',
        'exception' => new RuntimeException('carol@example.com bounced'),
    ]);

    $record = $handler->getRecords()[0];
    expect($record->message)->toBe('Failed to send to [email]')
        ->and($record->context)->not->toHaveKey('ip')
        ->and($record->context['error'])->toBe('Mailbox [email] unavailable')
        ->and($record->context['exception']['message'])->toBe('[email] bounced');
});
