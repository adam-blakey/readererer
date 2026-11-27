<?php

use App\Models\EmailRecipient;
use App\Support\SystemChecks;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Schedule::command('reminders:send')->everyMinute()->withoutOverlapping();
Schedule::command('health:check')->everyFiveMinutes();
Schedule::command('membermojo:sync')->dailyAt(config('services.membermojo.time'));
Schedule::call(fn () => EmailRecipient::deleteSnapshots(EmailRecipient::where('created_at', '<', now()->subDays(EmailRecipient::SNAPSHOT_DAYS))))
    ->name('delete-email-snapshots')
    ->daily();
Schedule::call(fn () => Cache::forever(SystemChecks::SCHEDULER_HEARTBEAT, now()->toIso8601String()))
    ->name('scheduler-heartbeat')
    ->everyMinute();
