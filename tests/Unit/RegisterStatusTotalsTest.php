<?php

use App\Enums\RegisterStatus;
use App\Models\RegisterEntry;

test('entries are counted by their register status', function () {
    $entries = collect([
        new RegisterEntry(['status' => RegisterStatus::Present]),
        new RegisterEntry(['status' => RegisterStatus::Present]),
        new RegisterEntry(['status' => RegisterStatus::Late]),
        new RegisterEntry(['status' => RegisterStatus::Absent]),
    ]);

    expect(register_status_totals($entries, 4))->toBe([
        'unmarked' => 0,
        'present' => 2,
        'absent' => 1,
        'late' => 1,
    ]);
});

test('members without an entry count as unmarked', function () {
    $entries = collect([new RegisterEntry(['status' => RegisterStatus::Present])]);

    expect(register_status_totals($entries, 5)['unmarked'])->toBe(4);
});

test('an empty register counts every member as unmarked', function () {
    expect(register_status_totals(collect(), 3))->toBe([
        'unmarked' => 3,
        'present' => 0,
        'absent' => 0,
        'late' => 0,
    ]);
});

test('more entries than expected members never gives a negative count', function () {
    $entries = collect([
        new RegisterEntry(['status' => RegisterStatus::Present]),
        new RegisterEntry(['status' => RegisterStatus::Present]),
    ]);

    expect(register_status_totals($entries, 1)['unmarked'])->toBe(0);
});
