<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Schedule::command() starts a new process through proc_open, which the host disables.
Schedule::call(fn () => Artisan::call('reminders:send') === 0)->name('reminders:send')->everyFifteenMinutes()->withoutOverlapping();
Schedule::call(fn () => Artisan::call('health:check') === 0)->name('health:check')->everyFiveMinutes();
