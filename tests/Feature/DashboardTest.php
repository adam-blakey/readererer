<?php

use App\Enums\UserRole;
use App\Features\DashboardPartAndSeat;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\TermDate;
use Laravel\Pennant\Feature;

test('the dashboard shows the part and seat for each ensemble when the flag is on', function () {
    config(['app.readererer_feature_flags' => [DashboardPartAndSeat::class => ['enabled' => true]]]);
    Feature::flushCache();
    $member = make_user(UserRole::Member);
    join_ensemble($member, Ensemble::factory()->create(), InstrumentFamily::factory()->create(['name' => 'Oboes']), 'B', 3);
    join_ensemble_without_instrument($member, Ensemble::factory()->create());

    $this->actingAs($member)->get('/dashboard')
        ->assertOk()
        ->assertSee('Oboes')
        ->assertSee('Row B, seat 3')
        ->assertSee('No part')
        ->assertSee('No seat');
});

test('the dashboard hides the part and seat while the flag is off', function () {
    $member = make_user(UserRole::Member);
    join_ensemble($member, Ensemble::factory()->create(), InstrumentFamily::factory()->create(['name' => 'Oboes']), 'B', 3);

    $this->actingAs($member)->get('/dashboard')->assertOk()->assertDontSee('Oboes')->assertDontSee('Row B, seat 3');
});

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
    $concert = tap(TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addMonth(),
        'end_datetime' => now()->addMonth()->addHours(3),
    ]), fn (TermDate $termDate) => $termDate->concert_ensembles()->attach($ensemble));

    $response = $this->actingAs($member)->get('/dashboard');

    $response->assertOk();
    $response->assertViewIs('dashboard.index');
    expect($response->viewData('ensembles')->pluck('id')->all())->toBe([$ensemble->id]);
    expect($response->viewData('nextRehearsal')->id)->toBe($rehearsal->id);
    expect($response->viewData('nextConcerts')->pluck('id')->all())->toBe([$concert->id]);
});

test('the dashboard renders upcoming dates in words rather than raw datetimes', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $term = Term::factory()->create();
    $start = now()->addDays(5)->setTime(19, 0);
    $rehearsal = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => $start,
        'end_datetime' => $start->copy()->setTime(21, 30),
    ]);

    $response = $this->actingAs($member)->get('/dashboard');

    $response->assertOk();
    $response->assertSee($rehearsal->schedule_label);
    $response->assertSee('In 5 days');
    $response->assertDontSee($start->format('Y-m-d H:i:s'));
});

test('concerts for other ensembles are not shown as the member\'s next concerts', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $term = Term::factory()->create();
    tap(TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addMonth(),
        'end_datetime' => now()->addMonth()->addHours(3),
    ]), fn (TermDate $termDate) => $termDate->concert_ensembles()->attach($otherEnsemble));

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
    $setupGroup = SetupGroup::factory()->create();
    $driver = make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id]);
    join_ensemble($driver, Ensemble::factory()->create());
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
    expect($response->viewData('nextSetup')->id)->toBe($termDate->id);
    $response->assertSee("Next time you're driving the van");
    $response->assertSee('Next time your group is setting up');
});

test('a van drive the user was assigned outside their setup group is shown', function () {
    $member = make_user(UserRole::Member);
    join_ensemble($member, Ensemble::factory()->create());

    $termDate = TermDate::forceCreate([
        'term_id' => Term::factory()->create()->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
        'setup_group_id' => SetupGroup::factory()->create()->id,
        'van_driver_id' => $member->id,
    ]);

    $response = $this->actingAs($member)->get('/dashboard');

    expect($response->viewData('nextVanDrive')->id)->toBe($termDate->id);
    $response->assertSee("Next time you're driving the van");
});

test('members who never drive the van are not shown a van driving section', function () {
    $setupGroup = SetupGroup::factory()->create();
    $member = make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id]);
    join_ensemble($member, Ensemble::factory()->create());

    $response = $this->actingAs($member)->get('/dashboard');

    $response->assertOk();
    expect($response->viewData('drivesTheVan'))->toBeFalse();
    $response->assertDontSee("Next time you're driving the van");
    $response->assertSee('Next time your group is setting up');
});

test('a member in no ensemble sees an empty state instead of rehearsals, setup and van driving', function () {
    $setupGroup = SetupGroup::factory()->create();
    $member = make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id]);
    $setupGroup->van_drivers()->attach($member->id, ['sort' => 0]);

    TermDate::forceCreate([
        'term_id' => Term::factory()->create()->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
        'setup_group_id' => $setupGroup->id,
    ]);

    $response = $this->actingAs($member)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('You are not currently a member of any ensembles.');
    $response->assertDontSee('Upcoming rehearsals and concerts');
    $response->assertDontSee('Your setup group');
    $response->assertDontSee("Next time you're driving the van");
    $response->assertViewMissing('nextRehearsal');
});

test('an ensemble login that is not linked to an ensemble is refused rather than erroring', function () {
    $this->actingAs(make_user(UserRole::Ensemble))->get('/dashboard')->assertForbidden();
});

test('moderators and admins see recent attendance updates and notifications on the dashboard', function (UserRole $role) {
    $response = $this->actingAs(make_user($role))->get('/dashboard')->assertOk();

    $response->assertSee('Attendance updates')
        ->assertSee('href="'.route('attendance.index').'"', false)
        ->assertSee('href="'.route('notifications.index').'"', false);
})->with([UserRole::Moderator, UserRole::Admin]);

test('members do not see attendance updates or notifications on the dashboard', function () {
    $response = $this->actingAs(make_user(UserRole::Member))->get('/dashboard')->assertOk();

    $response->assertDontSee('Attendance updates')
        ->assertDontSee('href="'.route('notifications.index').'"', false);
    expect($response->viewData('attendanceUpdates'))->toBeNull()
        ->and($response->viewData('notifications'))->toBeNull();
});
