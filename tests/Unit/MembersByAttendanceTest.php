<?php

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;

function make_grouping_term_date(): TermDate
{
    return TermDate::forceCreate([
        'term_id' => Term::factory()->create()->id,
        'start_datetime' => now()->addDay()->setTime(19, 0),
        'end_datetime' => now()->addDay()->setTime(21, 0),
    ]);
}

function record_grouping_attendance(User $member, TermDate $termDate, AttendanceStatus $status, ?Carbon\Carbon $createdAt = null): Attendance
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

test('members are grouped by the status they last recorded', function () {
    config(['app.readererer_assume_attending' => false]);

    $termDate = make_grouping_term_date();
    $attending = User::factory()->create();
    $notAttending = User::factory()->create();
    $unknown = User::factory()->create();

    record_grouping_attendance($attending, $termDate, AttendanceStatus::Attending);
    record_grouping_attendance($notAttending, $termDate, AttendanceStatus::NotAttending);

    $members = User::with('attendances')->whereIn('id', [$attending->id, $notAttending->id, $unknown->id])->get();
    $groups = members_by_attendance($members, $termDate);

    expect($groups['attending']->pluck('id')->all())->toBe([$attending->id]);
    expect($groups['not_attending']->pluck('id')->all())->toBe([$notAttending->id]);
    expect($groups['unknown']->pluck('id')->all())->toBe([$unknown->id]);
});

test('only the latest record decides which group a member lands in', function () {
    config(['app.readererer_assume_attending' => false]);

    $termDate = make_grouping_term_date();
    $member = User::factory()->create();

    record_grouping_attendance($member, $termDate, AttendanceStatus::Attending, now()->subDays(2));
    record_grouping_attendance($member, $termDate, AttendanceStatus::NotAttending, now()->subDay());

    $members = User::with('attendances')->whereKey($member->id)->get();
    $groups = members_by_attendance($members, $termDate);

    expect($groups['attending'])->toHaveCount(0);
    expect($groups['not_attending']->pluck('id')->all())->toBe([$member->id]);
});

test('unanswered members join the attending group when attendance is assumed', function () {
    config(['app.readererer_assume_attending' => true]);

    $termDate = make_grouping_term_date();
    $attending = User::factory()->create();
    $unknown = User::factory()->create();

    record_grouping_attendance($attending, $termDate, AttendanceStatus::Attending);

    $members = User::with('attendances')->whereIn('id', [$attending->id, $unknown->id])->get();
    $groups = members_by_attendance($members, $termDate);

    expect($groups)->not->toHaveKey('unknown');
    expect($groups['attending']->pluck('id')->all())->toEqualCanonicalizing([$attending->id, $unknown->id]);
});

test('the status of a member with no record at all is unknown', function () {
    $termDate = make_grouping_term_date();
    $member = User::with('attendances')->whereKey(User::factory()->create()->id)->first();

    expect(member_attendance_status($member, $termDate))->toBe(AttendanceStatus::Unknown);
});

test('a member\'s instrument family comes from the ensemble they are playing with', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $member = User::factory()->create();

    join_ensemble($member, $ensemble, InstrumentFamily::create(['name' => 'Strings']));
    join_ensemble($member, $otherEnsemble, InstrumentFamily::create(['name' => 'Brass']));

    $member->load('ensembles');

    expect(member_instrument_family_name($member, $otherEnsemble))->toBe('Brass');
});

test('a member with no instrument family falls back to a placeholder', function () {
    $ensemble = Ensemble::factory()->create();
    $member = User::factory()->create();

    join_ensemble_without_instrument($member, $ensemble);
    $member->load('ensembles');

    expect(member_instrument_family_name($member))->toBe('No instrument');
});
