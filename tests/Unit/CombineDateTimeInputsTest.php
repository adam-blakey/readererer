<?php

use App\Http\Middleware\CombineDateTimeInputs;
use Illuminate\Http\Request;

test('date and time halves are joined into one value, at any depth', function () {
    $request = Request::create('/', 'POST', [
        'starts_at' => ['date' => '2026-03-04', 'time' => '19:30'],
        'empty' => ['date' => null, 'time' => null],
        'no_time' => ['date' => '2026-03-04', 'time' => null],
        'twelve_hour' => ['date' => '2026-03-04', 'time' => '7:30pm'],
        'term_dates' => [3 => ['start_datetime' => ['date' => '2026-03-04', 'time' => '07:05'], 'ensemble_ids' => ['1', '2']]],
        'name' => 'Spring',
    ]);

    (new CombineDateTimeInputs)->handle($request, fn () => response(''));

    expect($request->all())->toBe([
        'starts_at' => '2026-03-04T19:30',
        'empty' => null,
        'no_time' => '2026-03-04T',
        'twelve_hour' => '2026-03-04T19:30',
        'term_dates' => [3 => ['start_datetime' => '2026-03-04T07:05', 'ensemble_ids' => ['1', '2']]],
        'name' => 'Spring',
    ]);
});
