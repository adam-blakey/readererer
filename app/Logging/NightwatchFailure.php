<?php

namespace App\Logging;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class NightwatchFailure
{
    public const CACHE_KEY = 'nightwatch.last_failure';

    public const THROTTLE_MINUTES = 5;

    public static function note(Throwable $e): void
    {
        if (! Cache::add(self::CACHE_KEY.'.throttle', true, now()->addMinutes(self::THROTTLE_MINUTES))) {
            return;
        }

        $reason = redact_personal_data($e->getMessage());

        Cache::forever(self::CACHE_KEY, ['at' => now()->getTimestamp(), 'reason' => $reason]);

        // The daily channel, not the default stack, which may include the Nightwatch channel that just failed.
        Log::channel('daily')->warning('Sending metrics to Nightwatch failed', ['exception' => $e::class, 'reason' => $reason]);
    }

    public static function latest(): ?object
    {
        $failure = Cache::get(self::CACHE_KEY);

        return $failure ? (object) [...$failure, 'at' => now()->setTimestamp($failure['at'])] : null;
    }
}
