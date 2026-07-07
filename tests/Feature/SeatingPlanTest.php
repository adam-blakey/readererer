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
    expect($grouped->get('unassigned')->pluck('id')->all())->toBe([$unseated->id]);
    expect($grouped->get('A')->pluck('id')->all())->toBe([$seated->id]);
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
