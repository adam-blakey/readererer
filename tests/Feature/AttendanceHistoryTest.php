<?php

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;

function history_term_date(Term $term, string $start): TermDate
{
    return TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => $start,
        'end_datetime' => $start.' +2 hours',
    ]);
}

function record_history_change(User $member, TermDate $termDate, Ensemble $ensemble, AttendanceStatus $status, User $editor, string $at): Attendance
{
    $attendance = Attendance::create([
        'user_id' => $member->id,
        'term_date_id' => $termDate->id,
        'ensemble_id' => $ensemble->id,
        'status' => $status,
        'edit_user_id' => $editor->id,
        'edit_ip' => '203.0.113.9',
    ]);
    $attendance->forceFill(['created_at' => $at])->save();

    return $attendance;
}

function history_url(Ensemble $ensemble, Term $term, User $member): string
{
    return route('attendance.poll.history', ['ensemble' => $ensemble->slug, 'term' => $term->slug, 'user' => $member]);
}

test('the history lists a member\'s changes for the term newest first', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = history_term_date($term, '2030-03-04 19:30');
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $firstEditor = make_user(UserRole::Member, ['first_name' => 'Fiona', 'last_name' => 'First']);
    $secondEditor = make_user(UserRole::Moderator, ['first_name' => 'Sam', 'last_name' => 'Second']);

    $older = record_history_change($member, $termDate, $ensemble, AttendanceStatus::Attending, $firstEditor, '2030-01-01 10:00');
    $newer = record_history_change($member, $termDate, $ensemble, AttendanceStatus::NotAttending, $secondEditor, '2030-01-02 10:00');
    $secondEditor->delete();

    $response = $this->actingAs(make_user(UserRole::Moderator))->get(history_url($ensemble, $term, $member));

    $response->assertOk()
        ->assertSeeInOrder([$termDate->date_label, 'Not attending', 'Sam Second', 'Wed, 2 Jan 2030, 10:00', 'Attending', 'Fiona First', 'Tue, 1 Jan 2030, 10:00'])
        ->assertDontSee('203.0.113.9');
    expect($response->viewData('attendances')->pluck('id')->all())->toBe([$newer->id, $older->id]);
});

test('the history only includes that term and ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $otherTerm = Term::factory()->create();
    $termDate = history_term_date($term, '2030-03-04 19:30');
    $otherTermDate = history_term_date($otherTerm, '2030-06-04 19:30');
    $member = make_user(UserRole::Member);
    $editor = make_user(UserRole::Admin);

    $kept = record_history_change($member, $termDate, $ensemble, AttendanceStatus::Attending, $editor, '2030-01-01 10:00');
    record_history_change($member, $termDate, $otherEnsemble, AttendanceStatus::Attending, $editor, '2030-01-01 10:00');
    record_history_change($member, $otherTermDate, $ensemble, AttendanceStatus::Attending, $editor, '2030-01-01 10:00');
    record_history_change(make_user(), $termDate, $ensemble, AttendanceStatus::Attending, $editor, '2030-01-01 10:00');

    $response = $this->actingAs($editor)->get(history_url($ensemble, $term, $member));

    expect($response->viewData('attendances')->pluck('id')->all())->toBe([$kept->id]);
});

test('a member with no changes shows never updated', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $member = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Moderator))
        ->get(history_url($ensemble, $term, $member))
        ->assertOk()
        ->assertSee('Never updated.');
});

test('the history requires moderator or above', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $url = history_url($ensemble, $term, $member);

    $ensembleLogin = make_user(UserRole::Ensemble);
    join_ensemble($ensembleLogin, $ensemble);
    $bandmate = make_user(UserRole::Member);
    join_ensemble($bandmate, $ensemble);

    $this->get($url)->assertForbidden();
    $this->actingAs($ensembleLogin)->get($url)->assertForbidden();
    $this->actingAs($bandmate)->get($url)->assertForbidden();
    $this->actingAs(make_user(UserRole::Moderator))->get($url)->assertOk();
    $this->actingAs(make_user(UserRole::Admin))->get($url)->assertOk();
});

test('the poll shows a history button only to those who may see the history', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    history_term_date($term, '2030-03-04 19:30');
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $pollUrl = route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]);
    $historyUrl = history_url($ensemble, $term, $member);

    $this->actingAs(make_user(UserRole::Moderator))->get($pollUrl)
        ->assertOk()
        ->assertSee($historyUrl, false)
        ->assertSee('View edit history for '.$member->name);

    $this->actingAs($member)->get($pollUrl)
        ->assertOk()
        ->assertDontSee($historyUrl, false);
});
