<?php

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;

function make_term_date(?Term $term = null): TermDate
{
    return TermDate::forceCreate([
        'term_id' => ($term ?? Term::factory()->create())->id,
        'start_datetime' => now()->addDay()->setTime(19, 0),
        'end_datetime' => now()->addDay()->setTime(21, 0),
    ]);
}

function record_attendance(User $member, TermDate $termDate, AttendanceStatus $status, ?Carbon\Carbon $createdAt = null): Attendance
{
    $attendance = Attendance::create([
        'user_id' => $member->id,
        'term_date_id' => $termDate->id,
        'ensemble_id' => Ensemble::factory()->create()->id,
        'status' => $status,
        'edit_user_id' => $member->id,
        'edit_ip' => '127.0.0.1',
    ]);

    if ($createdAt) {
        $attendance->created_at = $createdAt;
        $attendance->save();
    }

    return $attendance;
}

test('members are counted by their attendance status', function () {
    config(['app.readererer_assume_attending' => false]);

    $termDate = make_term_date();
    $attending = User::factory()->create();
    $notAttending = User::factory()->create();
    $unknown = User::factory()->create();

    record_attendance($attending, $termDate, AttendanceStatus::Attending);
    record_attendance($notAttending, $termDate, AttendanceStatus::NotAttending);

    $members = User::with('attendances')->whereIn('id', [$attending->id, $notAttending->id, $unknown->id])->get();

    expect(member_status_totals($members, $termDate))->toBe([
        'attending' => 1,
        'not_attending' => 1,
        'unknown' => 1,
    ]);
});

test('a member with no attendance record counts as unknown', function () {
    config(['app.readererer_assume_attending' => false]);

    $termDate = make_term_date();
    $member = User::factory()->create();

    $members = User::with('attendances')->whereKey($member->id)->get();

    expect(member_status_totals($members, $termDate))->toBe([
        'attending' => 0,
        'not_attending' => 0,
        'unknown' => 1,
    ]);
});

test('only the latest attendance record per member counts', function () {
    config(['app.readererer_assume_attending' => false]);

    $termDate = make_term_date();
    $member = User::factory()->create();

    record_attendance($member, $termDate, AttendanceStatus::Attending, now()->subDays(2));
    record_attendance($member, $termDate, AttendanceStatus::NotAttending, now()->subDay());

    $members = User::with('attendances')->whereKey($member->id)->get();

    expect(member_status_totals($members, $termDate))->toBe([
        'attending' => 0,
        'not_attending' => 1,
        'unknown' => 0,
    ]);
});

test('attendance for a different term date is ignored', function () {
    config(['app.readererer_assume_attending' => false]);

    $term = Term::factory()->create();
    $termDate = make_term_date($term);
    $otherTermDate = make_term_date($term);
    $member = User::factory()->create();

    record_attendance($member, $otherTermDate, AttendanceStatus::Attending);

    $members = User::with('attendances')->whereKey($member->id)->get();

    expect(member_status_totals($members, $termDate))->toBe([
        'attending' => 0,
        'not_attending' => 0,
        'unknown' => 1,
    ]);
});

test('unknown members count as attending when assume_attending is enabled', function () {
    config(['app.readererer_assume_attending' => true]);

    $termDate = make_term_date();
    $attending = User::factory()->create();
    $notAttending = User::factory()->create();
    $unknown = User::factory()->create();

    record_attendance($attending, $termDate, AttendanceStatus::Attending);
    record_attendance($notAttending, $termDate, AttendanceStatus::NotAttending);

    $members = User::with('attendances')->whereIn('id', [$attending->id, $notAttending->id, $unknown->id])->get();

    expect(member_status_totals($members, $termDate))->toBe([
        'attending' => 2,
        'not_attending' => 1,
    ]);
});
