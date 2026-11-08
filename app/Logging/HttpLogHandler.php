<?php

namespace App\Logging;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

class HttpLogHandler extends AbstractProcessingHandler
{
    private array $buffer = [];

    private bool $flushesOnShutdown = false;

    public function __construct(
        private readonly ?string $url,
        private readonly ?string $token = null,
        int|string|Level $level = Level::Debug,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if (! $this->url) {
            return;
        }

        $this->buffer[] = ['dt' => $record->datetime->format(DATE_RFC3339_EXTENDED), ...$record->formatted];

        if (! $this->flushesOnShutdown) {
            // Shutdown functions run after the response is sent and before the container is torn down.
            register_shutdown_function($this->close(...));
            $this->flushesOnShutdown = true;
        }
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new NormalizerFormatter(DATE_RFC3339_EXTENDED);
    }

    public function close(): void
    {
        if ($this->buffer === []) {
            return;
        }

        $events = $this->buffer;
        $this->buffer = [];

        try {
            Http::timeout(3)
                ->when($this->token, fn (PendingRequest $request) => $request->withToken($this->token))
                ->post($this->url, $events)
                ->throw();
        } catch (Throwable $e) {
            error_log('Could not send '.count($events).' log entries: '.$e->getMessage());
        }
    }
}
