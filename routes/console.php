<?php

use App\Models\EmailRecipient;
use Illuminate\Support\Facades\Schedule;

Schedule::command('reminders:send')->everyMinute()->withoutOverlapping();
Schedule::command('health:check')->everyFiveMinutes();
Schedule::command('membermojo:sync')->dailyAt(config('services.membermojo.time'));
Schedule::call(fn () => EmailRecipient::deleteSnapshots(EmailRecipient::where('created_at', '<', now()->subDays(EmailRecipient::SNAPSHOT_DAYS))))
    ->name('delete-email-snapshots')
    ->daily();
