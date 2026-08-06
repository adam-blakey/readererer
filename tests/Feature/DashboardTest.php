<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\TermDate;

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

test('the dashboard shows the next date against each setup group', function () {
    $groupA = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);
    $groupB = SetupGroup::create(['name' => 'Group B', 'week' => 2, 'color' => 'green']);
    $member = make_user(UserRole::Member, ['setup_group_id' => $groupA->id]);

    $term = Term::factory()->create();
    // The one further out should not win over the group's nearest upcoming date.
    TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeeks(3),
        'end_datetime' => now()->addWeeks(3)->addHours(2),
        'setup_group_id' => $groupA->id,
    ]);
    $nextForA = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
        'setup_group_id' => $groupA->id,
    ]);
    $nextForB = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeeks(2),
        'end_datetime' => now()->addWeeks(2)->addHours(2),
        'setup_group_id' => $groupB->id,
    ]);

    $response = $this->actingAs($member)->get('/dashboard');

    $response->assertOk();
    expect($response->viewData('setupGroups')->pluck('id')->all())->toBe([$groupA->id, $groupB->id]);

    $nextDates = $response->viewData('nextSetupGroupDates');
    expect($nextDates->get($groupA->id)->id)->toBe($nextForA->id);
    expect($nextDates->get($groupB->id)->id)->toBe($nextForB->id);

    $response->assertSee('Group A');
    $response->assertSee('Group B');
    $response->assertSee($nextForA->schedule_label);
    $response->assertSee($nextForB->schedule_label);
});

test('a setup group with only past dates has no next date on the dashboard', function () {
    $group = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);
    $member = make_user(UserRole::Member, ['setup_group_id' => $group->id]);

    $term = Term::factory()->create();
    TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->subWeek(),
        'end_datetime' => now()->subWeek()->addHours(2),
        'setup_group_id' => $group->id,
    ]);

    $response = $this->actingAs($member)->get('/dashboard');

    $response->assertOk();
    expect($response->viewData('nextSetupGroupDates')->get($group->id))->toBeNull();
    $response->assertSee('No upcoming dates.');
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
