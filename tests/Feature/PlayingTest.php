<?php

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;

function make_playing_date(array $attributes = []): TermDate
{
    return TermDate::forceCreate(array_merge([
        'term_id' => Term::factory()->create()->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ], $attributes));
}

function record_playing_attendance(User $member, TermDate $termDate, Ensemble $ensemble, AttendanceStatus $status): Attendance
{
    return Attendance::create([
        'user_id' => $member->id,
        'term_date_id' => $termDate->id,
        'ensemble_id' => $ensemble->id,
        'status' => $status,
        'edit_user_id' => $member->id,
        'edit_ip' => '127.0.0.1',
    ]);
}

test('the index lists upcoming rehearsals and the member\'s own concerts', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $rehearsal = make_playing_date();
    $ownConcert = make_playing_date([
        'start_datetime' => now()->addMonth(),
        'end_datetime' => now()->addMonth()->addHours(3),
        'concert_ensemble_id' => $ensemble->id,
    ]);
    make_playing_date([
        'start_datetime' => now()->addMonth(),
        'end_datetime' => now()->addMonth()->addHours(3),
        'concert_ensemble_id' => $otherEnsemble->id,
    ]);
    make_playing_date([
        'start_datetime' => now()->subWeek(),
        'end_datetime' => now()->subWeek()->addHours(2),
    ]);

    $response = $this->actingAs($member)->get(route('playing.index'));

    $response->assertOk();
    $response->assertViewIs('playing.index');
    expect($response->viewData('term_dates')->pluck('id')->all())->toBe([$rehearsal->id, $ownConcert->id]);
});

test('the index summarises how many people are playing at each date', function () {
    config(['app.readererer_assume_attending' => false]);

    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    $playing = make_user(UserRole::Member);
    $notPlaying = make_user(UserRole::Member);
    foreach ([$member, $playing, $notPlaying] as $user) {
        join_ensemble($user, $ensemble);
    }

    $rehearsal = make_playing_date();
    record_playing_attendance($playing, $rehearsal, $ensemble, AttendanceStatus::Attending);
    record_playing_attendance($notPlaying, $rehearsal, $ensemble, AttendanceStatus::NotAttending);

    $response = $this->actingAs($member)->get(route('playing.index'));

    expect($response->viewData('totals')[$rehearsal->id])->toBe([
        'attending' => 1,
        'not_attending' => 1,
        'unknown' => 1,
    ]);
});

test('a member who belongs to no ensemble has nothing to see', function () {
    make_playing_date();

    $response = $this->actingAs(make_user(UserRole::Member))->get(route('playing.index'));

    $response->assertOk();
    expect($response->viewData('term_dates'))->toHaveCount(0);
});

test('the show page splits players into playing, not playing and unanswered', function () {
    config(['app.readererer_assume_attending' => false]);

    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    $notPlaying = make_user(UserRole::Member);
    $unanswered = make_user(UserRole::Member);
    foreach ([$member, $notPlaying, $unanswered] as $user) {
        join_ensemble($user, $ensemble);
    }

    $concert = make_playing_date(['concert_ensemble_id' => $ensemble->id]);
    record_playing_attendance($member, $concert, $ensemble, AttendanceStatus::Attending);
    record_playing_attendance($notPlaying, $concert, $ensemble, AttendanceStatus::NotAttending);

    $response = $this->actingAs($member)->get(route('playing.show', $concert));

    $response->assertOk();
    $response->assertViewIs('playing.show');
    $response->assertSee($concert->name);
    $response->assertSee($notPlaying->name);
    // Players are listed under the instrument family they play in.
    $response->assertSee('Test Family');

    $groups = $response->viewData('groups');
    expect($groups['attending']->pluck('id')->all())->toBe([$member->id]);
    expect($groups['not_attending']->pluck('id')->all())->toBe([$notPlaying->id]);
    expect($groups['unknown']->pluck('id')->all())->toBe([$unanswered->id]);
    expect($response->viewData('ensemble')->id)->toBe($ensemble->id);
});

test('unanswered members are shown as playing when attendance is assumed', function () {
    config(['app.readererer_assume_attending' => true]);

    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    $unanswered = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    join_ensemble($unanswered, $ensemble);

    $concert = make_playing_date(['concert_ensemble_id' => $ensemble->id]);

    $response = $this->actingAs($member)->get(route('playing.show', $concert));

    $groups = $response->viewData('groups');
    expect($groups)->not->toHaveKey('unknown');
    expect($groups['attending']->pluck('id')->all())->toEqualCanonicalizing([$member->id, $unanswered->id]);
});

test('a rehearsal shows the members of every ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    $otherMember = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    join_ensemble($otherMember, $otherEnsemble);

    $rehearsal = make_playing_date();

    $response = $this->actingAs($member)->get(route('playing.show', $rehearsal));

    $response->assertOk();
    $players = collect($response->viewData('groups'))->flatten();
    expect($players->pluck('id')->all())->toEqualCanonicalizing([$member->id, $otherMember->id]);
    expect($response->viewData('ensemble'))->toBeNull();
});

test('a concert is only visible to members of the ensemble playing it', function () {
    $ensemble = Ensemble::factory()->create();
    $concert = make_playing_date(['concert_ensemble_id' => $ensemble->id]);

    $outsider = make_user(UserRole::Member);
    join_ensemble($outsider, Ensemble::factory()->create());
    $this->actingAs($outsider)->get(route('playing.show', $concert))->assertForbidden();

    // A moderator does not have to be in the ensemble to look at its concert.
    $this->actingAs(make_user(UserRole::Moderator))->get(route('playing.show', $concert))->assertOk();
});

test('guests and ensemble logins may not see who is playing', function () {
    $ensemble = Ensemble::factory()->create();
    $rehearsal = make_playing_date();

    $this->get(route('playing.index'))->assertForbidden();
    $this->get(route('playing.show', $rehearsal))->assertForbidden();

    $ensembleLogin = make_user(UserRole::Ensemble);
    join_ensemble($ensembleLogin, $ensemble);
    $this->actingAs($ensembleLogin)->get(route('playing.index'))->assertForbidden();
    $this->actingAs($ensembleLogin)->get(route('playing.show', $rehearsal))->assertForbidden();
});

test('the shared ensemble login is not listed as somebody you are playing with', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    $ensembleLogin = make_user(UserRole::Ensemble);
    join_ensemble($member, $ensemble);
    join_ensemble($ensembleLogin, $ensemble);

    $concert = make_playing_date(['concert_ensemble_id' => $ensemble->id]);

    $response = $this->actingAs($member)->get(route('playing.show', $concert));

    $players = collect($response->viewData('groups'))->flatten();
    expect($players->pluck('id')->all())->toBe([$member->id]);
});
