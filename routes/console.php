<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Uploaded images outlive the record that pointed at them (an image replaced,
// cleared, or belonging to a purged record), so sweep the unreferenced ones
// overnight. Requires the host's cron to run `php artisan schedule:run`.
Schedule::command('images:prune')->dailyAt('03:00');
