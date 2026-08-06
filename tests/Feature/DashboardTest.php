<?php

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;

function record_dashboard_attendance(User $member, TermDate $termDate, Ensemble $ensemble, AttendanceStatus $status): Attendance
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

test('members see the dashboard with their ensembles and upcoming dates', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $term = Term::factory()->create();
    $rehearsal = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    $concert = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addMonth(),
        'end_datetime' => now()->addMonth()->addHours(3),
        'concert_ensemble_id' => $ensemble->id,
    ]);

    $response = $this->actingAs($member)->get('/dashboard');

    $response->assertOk();
    $response->assertViewIs('dashboard.index');
    expect($response->viewData('ensembles')->pluck('id')->all())->toBe([$ensemble->id]);
    expect($response->viewData('nextRehearsal')->id)->toBe($rehearsal->id);
    expect($response->viewData('nextConcerts')->pluck('id')->all())->toBe([$concert->id]);
});

test('concerts for other ensembles are not shown as the member\'s next concerts', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $term = Term::factory()->create();
    TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addMonth(),
        'end_datetime' => now()->addMonth()->addHours(3),
        'concert_ensemble_id' => $otherEnsemble->id,
    ]);

    $response = $this->actingAs($member)->get('/dashboard');

    expect($response->viewData('nextConcerts'))->toHaveCount(0);
});

test('past term dates are not offered as the next rehearsal', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $term = Term::factory()->create();
    TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->subWeek(),
        'end_datetime' => now()->subWeek()->addHours(2),
    ]);

    $response = $this->actingAs($member)->get('/dashboard');

    expect($response->viewData('nextRehearsal'))->toBeNull();
});

test('ensemble logins are shown their ensemble page instead of the dashboard', function () {
    $ensemble = Ensemble::factory()->create();
    $ensembleLogin = make_user(UserRole::Ensemble);
    join_ensemble($ensembleLogin, $ensemble);

    $response = $this->actingAs($ensembleLogin)->get('/dashboard');

    $response->assertOk();
    $response->assertViewIs('ensembles.show');
    expect($response->viewData('ensemble')->id)->toBe($ensemble->id);
});

test('the dashboard shows the user\'s next van drive from the setup group rotation', function () {
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);
    $driver = make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id]);
    $setupGroup->van_drivers()->attach($driver->id, ['sort' => 0]);

    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
        'setup_group_id' => $setupGroup->id,
    ]);

    $response = $this->actingAs($driver)->get('/dashboard');

    $response->assertOk();
    expect($response->viewData('nextVanDrive')->id)->toBe($termDate->id);
});

test('the next rehearsal is shown with every ensemble playing at it, the user\'s own marked', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'A band']);
    $otherEnsemble = Ensemble::factory()->create(['name' => 'B band']);
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    join_ensemble(make_user(UserRole::Member), $otherEnsemble);

    $term = Term::factory()->create();
    $rehearsal = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);

    $response = $this->actingAs($member)->get('/dashboard');

    $playingWith = $response->viewData('playingWith')[$rehearsal->id];
    expect($playingWith->pluck('ensemble.id')->all())->toBe([$ensemble->id, $otherEnsemble->id]);
    expect($playingWith->pluck('is_yours')->all())->toBe([true, false]);
    $response->assertSee($otherEnsemble->name);
});

test('a concert is played with the ensemble putting it on and nobody else', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    join_ensemble(make_user(UserRole::Member), $otherEnsemble);

    $term = Term::factory()->create();
    $concert = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addMonth(),
        'end_datetime' => now()->addMonth()->addHours(3),
        'concert_ensemble_id' => $ensemble->id,
    ]);

    $response = $this->actingAs($member)->get('/dashboard');

    expect($response->viewData('playingWith')[$concert->id]->pluck('ensemble.id')->all())->toBe([$ensemble->id]);
});

test('each ensemble is listed with how many of its members are playing', function () {
    config(['app.readererer_assume_attending' => false]);

    $ensemble = Ensemble::factory()->create();
    $emptyEnsemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    $notPlaying = make_user(UserRole::Member);
    $unanswered = make_user(UserRole::Member);
    // A shared ensemble login must not count as one of the players.
    $ensembleLogin = make_user(UserRole::Ensemble);
    foreach ([$member, $notPlaying, $unanswered, $ensembleLogin] as $user) {
        join_ensemble($user, $ensemble);
    }

    $term = Term::factory()->create();
    $rehearsal = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    record_dashboard_attendance($member, $rehearsal, $ensemble, AttendanceStatus::Attending);
    record_dashboard_attendance($notPlaying, $rehearsal, $ensemble, AttendanceStatus::NotAttending);

    $response = $this->actingAs($member)->get('/dashboard');

    $playingWith = $response->viewData('playingWith')[$rehearsal->id];
    // An ensemble with no members at all is not somebody you can play with.
    expect($playingWith->pluck('ensemble.id')->all())->toBe([$ensemble->id]);
    expect($playingWith->first()['totals'])->toBe([
        'attending' => 1,
        'not_attending' => 1,
        'unknown' => 1,
    ]);
    $response->assertDontSee($emptyEnsemble->name);
});

test('a member with nothing coming up has nobody to play with', function () {
    $member = make_user(UserRole::Member);
    join_ensemble($member, Ensemble::factory()->create());

    $response = $this->actingAs($member)->get('/dashboard');

    $response->assertOk();
    expect($response->viewData('playingWith'))->toHaveCount(0);
});
