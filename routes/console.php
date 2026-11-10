<?php

use App\Models\EmailRecipient;
use Illuminate\Support\Facades\Schedule;

Schedule::command('reminders:send')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('health:check')->everyFiveMinutes();
Schedule::call(fn () => EmailRecipient::deleteSnapshots(EmailRecipient::where('created_at', '<', now()->subDays(EmailRecipient::SNAPSHOT_DAYS))))
    ->name('delete-email-snapshots')
    ->daily();
