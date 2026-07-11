<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;

test('the seating plan page requires permission to update the ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $url = route('ensembles.seating-plan.show', $ensemble);

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $this->actingAs($member)->get($url)->assertForbidden();

    $this->actingAs(make_user(UserRole::Moderator))->get($url)->assertForbidden();
    $this->actingAs(make_user(UserRole::Admin))->get($url)->assertOk();
});

test('the seating plan groups members by seat row', function () {
    // Seat rows are letters on the seating plan page (the view builds a
    // range('A', ...) from the highest row).
    $ensemble = Ensemble::factory()->create();
    $seated = make_user(UserRole::Member);
    $unseated = make_user(UserRole::Member);
    join_ensemble($seated, $ensemble, null, 'A', 2);
    join_ensemble($unseated, $ensemble);

    $response = $this->actingAs(make_user(UserRole::Admin))
        ->get(route('ensembles.seating-plan.show', $ensemble));

    $response->assertOk();
    $grouped = $response->viewData('grouped_users');
    expect($grouped->has('unassigned'))->toBeFalse();
    expect($grouped->get('A')->pluck('id')->all())->toBe([$seated->id]);
    expect($response->viewData('unassigned_users')->flatten(1)->pluck('id')->all())->toBe([$unseated->id]);
});

test('unassigned members are split up by instrument family', function () {
    $ensemble = Ensemble::factory()->create();
    $flautist = make_user(UserRole::Member);
    $trumpeter = make_user(UserRole::Member);
    $unknown = make_user(UserRole::Member);
    join_ensemble($flautist, $ensemble, make_instrument_family('Flutes'));
    join_ensemble($trumpeter, $ensemble, make_instrument_family('Trumpets'));
    join_ensemble_without_instrument($unknown, $ensemble);

    $response = $this->actingAs(make_user(UserRole::Admin))
        ->get(route('ensembles.seating-plan.show', $ensemble));

    $response->assertOk();
    $unassigned = $response->viewData('unassigned_users');
    expect($unassigned->get('Flutes')->pluck('id')->all())->toBe([$flautist->id]);
    expect($unassigned->get('Trumpets')->pluck('id')->all())->toBe([$trumpeter->id]);
    expect($unassigned->get('No instrument')->pluck('id')->all())->toBe([$unknown->id]);
});

test('the seating plan page offers downloads for upcoming rehearsals and own concerts', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $term = Term::factory()->create();

    $pastRehearsal = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->subWeek(),
        'end_datetime' => now()->subWeek()->addHours(2),
    ]);
    $upcomingRehearsal = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    $ownConcert = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeeks(2),
        'end_datetime' => now()->addWeeks(2)->addHours(2),
        'concert_ensemble_id' => $ensemble->id,
    ]);
    $otherConcert = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeeks(3),
        'end_datetime' => now()->addWeeks(3)->addHours(2),
        'concert_ensemble_id' => $otherEnsemble->id,
    ]);

    $response = $this->actingAs(make_user(UserRole::Admin))
        ->get(route('ensembles.seating-plan.show', $ensemble));

    $response->assertOk();
    $dates = $response->viewData('upcoming_term_dates');
    expect($dates->pluck('id')->all())->toBe([$upcomingRehearsal->id, $ownConcert->id]);
    $response->assertSee(route('seating-plan.download', ['ensemble' => $ensemble, 'termDate' => $upcomingRehearsal]));
});

test('updating the seating plan stores seat rows and columns on the pivot', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('ensembles.seating-plan.update', $ensemble), [
            'seating_plan' => json_encode([
                'A' => [['id' => $member->id, 'column' => 2]],
            ]),
        ])
        ->assertRedirect(route('ensembles.seating-plan.show', $ensemble));

    $pivot = $ensemble->users()->first()->pivot;
    expect($pivot->seat_row)->toEqual('A');
    expect($pivot->seat_column)->toEqual(2);
});

test('updating the seating plan is forbidden without permission to update the ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble, null, 'A', 1);

    $payload = [
        'seating_plan' => json_encode([
            'B' => [['id' => $member->id, 'column' => 5]],
        ]),
    ];

    $this->actingAs($member)
        ->post(route('ensembles.seating-plan.update', $ensemble), $payload)
        ->assertForbidden();

    $this->actingAs(make_user(UserRole::Moderator))
        ->post(route('ensembles.seating-plan.update', $ensemble), $payload)
        ->assertForbidden();

    // The seat was not moved.
    $pivot = $ensemble->users()->first()->pivot;
    expect($pivot->seat_row)->toEqual('A');
    expect($pivot->seat_column)->toEqual(1);
});

test('moving a member to unassigned clears their seat', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble, null, 1, 'A');

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('ensembles.seating-plan.update', $ensemble), [
            'seating_plan' => json_encode([
                'unassigned' => [['id' => $member->id]],
            ]),
        ])
        ->assertRedirect();

    $pivot = $ensemble->users()->first()->pivot;
    expect($pivot->seat_row)->toBeNull();
    expect($pivot->seat_column)->toBeNull();
});

test('the seating plan pdf downloads for authorised users', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);

    $member = make_user(UserRole::Member, ['image' => null]);
    join_ensemble($member, $ensemble, null, 1, 'A');

    $response = $this->actingAs(make_user(UserRole::Admin))
        ->get(route('seating-plan.download', ['ensemble' => $ensemble, 'termDate' => $termDate->id]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('the seating plan pdf is forbidden without permission to view the ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);

    $member = make_user(UserRole::Member);

    $this->actingAs($member)
        ->get(route('seating-plan.download', ['ensemble' => $ensemble, 'termDate' => $termDate->id]))
        ->assertForbidden();
});
