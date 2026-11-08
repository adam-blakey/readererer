<?php

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;

it('runs scheduled tasks in the scheduler process, since the host disables proc_open', function () {
    expect(app(Schedule::class)->events())
        ->toHaveCount(2)
        ->each->toBeInstanceOf(CallbackEvent::class);
});
