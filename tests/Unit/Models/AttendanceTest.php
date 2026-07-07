<?php

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;

function make_attendance(AttendanceStatus $status): Attendance
{
    $user = User::factory()->create();
    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => '2026-01-05 19:00:00',
        'end_datetime' => '2026-01-05 21:00:00',
    ]);
    $ensemble = Ensemble::factory()->create();

    return Attendance::create([
        'user_id' => $user->id,
        'term_date_id' => $termDate->id,
        'ensemble_id' => $ensemble->id,
        'status' => $status,
        'edit_user_id' => $user->id,
        'edit_ip' => '127.0.0.1',
    ]);
}

test('status is cast to the AttendanceStatus enum', function () {
    $attendance = make_attendance(AttendanceStatus::Attending);

    expect($attendance->fresh()->status)->toBe(AttendanceStatus::Attending);
});

test('name combines the ensemble and term names', function () {
    $attendance = make_attendance(AttendanceStatus::Attending);

    $expected = $attendance->ensemble->name.': '.$attendance->term_date->term->name;
    expect($attendance->name)->toBe($expected);
});

test('status text describes each status', function (AttendanceStatus $status, string $text) {
    config(['app.readererer_assume_attending' => false]);

    expect(make_attendance($status)->status_text)->toBe($text);
})->with([
    [AttendanceStatus::Attending, 'Attending'],
    [AttendanceStatus::NotAttending, 'Not attending'],
    [AttendanceStatus::Unknown, 'Unknown'],
]);

test('unknown status displays as attending when assume_attending is enabled', function () {
    config(['app.readererer_assume_attending' => true]);

    expect(make_attendance(AttendanceStatus::Unknown)->status_text)->toBe('Attending');
    expect(make_attendance(AttendanceStatus::NotAttending)->status_text)->toBe('Not attending');
});
