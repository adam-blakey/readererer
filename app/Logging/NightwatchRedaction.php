<?php

namespace App\Logging;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Log\Logger;
use Laravel\Nightwatch\Records\Command;
use Laravel\Nightwatch\Records\Exception;
use Laravel\Nightwatch\Records\OutgoingRequest;
use Laravel\Nightwatch\Records\Request;
use Monolog\Handler\GroupHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Throwable;

/**
 * Keeps personal data out of what is sent to Laravel Nightwatch: members are identified by ID only.
 */
class NightwatchRedaction
{
    public static function user(Authenticatable $user): array
    {
        return ['id' => $user->getAuthIdentifier()];
    }

    public static function request(Request $request): void
    {
        $origin = preg_replace('#^(\w+://[^/]+).*$#s', '$1', $request->url);
        $request->url = $request->routePath === '' ? redact_personal_data(strtok($request->url, '?')) : $origin.$request->routePath;
        $request->ip = '';
        $request->headers = new HeaderBag;
        $request->payload = new InputBag;
        $request->files = new FileBag;
    }

    public static function outgoingRequest(OutgoingRequest $request): void
    {
        $request->url = redact_personal_data(strtok($request->url, '?'));
    }

    public static function command(Command $command): void
    {
        $command->command = $command->name;
    }

    public static function exception(Exception $exception): void
    {
        $exception->message = redact_personal_data($exception->message);
    }

    /**
     * The `nightwatch` log channel's tap. Wrapping the handler, rather than adding a processor to the logger,
     * keeps the redaction out of the other channels when Nightwatch is part of a log stack.
     */
    public function __invoke(Logger $logger): void
    {
        $logger->setHandlers(array_map(
            fn (HandlerInterface $handler) => (new GroupHandler([$handler]))->pushProcessor(self::logRecord(...)),
            $logger->getHandlers(),
        ));
    }

    public static function logRecord(LogRecord $record): LogRecord
    {
        return $record->with(
            message: redact_personal_data($record->message),
            context: self::values($record->context),
            extra: self::values($record->extra),
        );
    }

    private static function values(array $values): array
    {
        unset($values['ip']);

        return array_map(fn ($value) => match (true) {
            is_string($value) => redact_personal_data($value),
            is_array($value) => self::values($value),
            $value instanceof Throwable => ['class' => $value::class, 'message' => redact_personal_data($value->getMessage()), 'file' => $value->getFile().':'.$value->getLine()],
            default => $value,
        }, $values);
    }
}
