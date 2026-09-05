<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\Term;

test('the generic edit form opts into the change check but the create form does not', function () {
    $instrumentFamily = InstrumentFamily::create(['name' => 'Bassoons']);
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)
        ->get(route('instrumentfamilys.edit', $instrumentFamily))
        ->assertOk()
        ->assertSee('data-dirty-check', false);

    $this->actingAs($admin)
        ->get(route('instrumentfamilys.create'))
        ->assertOk()
        ->assertDontSee('data-dirty-check', false);
});

test('the user edit form opts into the change check', function () {
    $user = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('users.edit', $user))
        ->assertOk()
        ->assertSee('data-dirty-check', false);
});

test('the ensemble edit form opts into the change check', function () {
    $ensemble = Ensemble::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('ensembles.edit', $ensemble))
        ->assertOk()
        ->assertSee('data-dirty-check', false);
});

test('the term edit form opts into the change check', function () {
    $term = Term::factory()->create();

    $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('terms.edit', $term))
        ->assertOk()
        ->assertSee('data-dirty-check', false);
});

test('the attendance poll and register forms opt into the change check', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = make_term_date($term);

    $moderator = make_user(UserRole::Moderator);
    join_ensemble($moderator, $ensemble);

    $this->actingAs($moderator)
        ->get(route('attendance.poll', ['ensemble' => $ensemble->slug, 'term' => $term->slug]))
        ->assertOk()
        ->assertSee('data-dirty-check', false);

    $this->actingAs($moderator)
        ->get(route('attendance.register.show', ['ensemble' => $ensemble->slug, 'termDate' => $termDate->id]))
        ->assertOk()
        ->assertSee('data-dirty-check', false);
});
