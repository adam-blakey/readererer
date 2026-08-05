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

/**
 * The ensembles listed against a term date, in the order the page shows them.
 */
function listed_ensembles($response, TermDate $termDate)
{
    return $response->viewData('ensembles')[$termDate->id];
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

test('a rehearsal is played with every ensemble, and the member\'s own is marked', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'A band']);
    $otherEnsemble = Ensemble::factory()->create(['name' => 'B band']);
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    join_ensemble(make_user(UserRole::Member), $otherEnsemble);

    $rehearsal = make_playing_date();

    $response = $this->actingAs($member)->get(route('playing.index'));

    $listed = listed_ensembles($response, $rehearsal);
    expect($listed->pluck('ensemble.id')->all())->toBe([$ensemble->id, $otherEnsemble->id]);
    expect($listed->pluck('is_yours')->all())->toBe([true, false]);
    $response->assertSee($otherEnsemble->name);
});

test('a concert is only played with the ensemble putting it on', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    join_ensemble(make_user(UserRole::Member), $otherEnsemble);

    $concert = make_playing_date(['concert_ensemble_id' => $ensemble->id]);

    $response = $this->actingAs($member)->get(route('playing.index'));

    expect(listed_ensembles($response, $concert)->pluck('ensemble.id')->all())->toBe([$ensemble->id]);
});

test('an ensemble with no members is not listed as somebody you are playing with', function () {
    $ensemble = Ensemble::factory()->create();
    $emptyEnsemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $rehearsal = make_playing_date();

    $response = $this->actingAs($member)->get(route('playing.index'));

    expect(listed_ensembles($response, $rehearsal)->pluck('ensemble.id')->all())->toBe([$ensemble->id]);
    $response->assertDontSee($emptyEnsemble->name);
});

test('each ensemble is listed with how many of its members are playing', function () {
    config(['app.readererer_assume_attending' => false]);

    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    $notPlaying = make_user(UserRole::Member);
    $unanswered = make_user(UserRole::Member);
    foreach ([$member, $notPlaying, $unanswered] as $user) {
        join_ensemble($user, $ensemble);
    }

    $rehearsal = make_playing_date();
    record_playing_attendance($member, $rehearsal, $ensemble, AttendanceStatus::Attending);
    record_playing_attendance($notPlaying, $rehearsal, $ensemble, AttendanceStatus::NotAttending);

    $response = $this->actingAs($member)->get(route('playing.index'));

    expect(listed_ensembles($response, $rehearsal)->first()['totals'])->toBe([
        'attending' => 1,
        'not_attending' => 1,
        'unknown' => 1,
    ]);
});

test('the shared ensemble login does not count towards an ensemble\'s players', function () {
    config(['app.readererer_assume_attending' => false]);

    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    $ensembleLogin = make_user(UserRole::Ensemble);
    join_ensemble($member, $ensemble);
    join_ensemble($ensembleLogin, $ensemble);

    $rehearsal = make_playing_date();

    $response = $this->actingAs($member)->get(route('playing.index'));

    expect(listed_ensembles($response, $rehearsal)->first()['totals'])->toBe([
        'attending' => 0,
        'not_attending' => 0,
        'unknown' => 1,
    ]);
});

test('a member who belongs to no ensemble has nothing to see', function () {
    make_playing_date();

    $response = $this->actingAs(make_user(UserRole::Member))->get(route('playing.index'));

    $response->assertOk();
    expect($response->viewData('term_dates'))->toHaveCount(0);
});

test('guests and ensemble logins may not see who they are playing with', function () {
    $ensemble = Ensemble::factory()->create();

    $this->get(route('playing.index'))->assertForbidden();

    $ensembleLogin = make_user(UserRole::Ensemble);
    join_ensemble($ensembleLogin, $ensemble);
    $this->actingAs($ensembleLogin)->get(route('playing.index'))->assertForbidden();
});
