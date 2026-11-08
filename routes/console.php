<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('reminders:send')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('health:check')->everyFiveMinutes();
