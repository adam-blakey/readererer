<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Term;

/*
 * The poll and the register are two views of the same date, so each links to
 * the other, and the upcoming dates on the dashboard and an ensemble's page
 * link into both.
 */

test('the register links to the poll for its term', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = make_term_date($term);

    $response = $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('attendance.register.show', ['ensemble' => $ensemble->slug, 'termDate' => $termDate->id]));

    $response->assertOk();
    $response->assertSee(route('attendance.poll', ['ensemble' => $ensemble, 'term' => $term]), false);
});

test('the poll links to the register for each of the term\'s dates', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $term = Term::factory()->create();

    $rehearsal = make_term_date($term);
    $ownConcert = make_term_date($term, $ensemble);
    $otherConcert = make_term_date($term, $otherEnsemble);

    $response = $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('attendance.poll', ['ensemble' => $ensemble->slug, 'term' => $term->slug]));

    $response->assertOk();
    $response->assertSee(route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $rehearsal]), false);
    $response->assertSee(route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $ownConcert]), false);
    // Another ensemble's concert is not part of this poll, so it has no register here.
    $response->assertDontSee(route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $otherConcert]), false);
});

test('the poll offers no register to members who cannot take one', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = make_term_date($term);

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $response = $this->actingAs($member)
        ->get(route('attendance.poll', ['ensemble' => $ensemble->slug, 'term' => $term->slug]));

    $response->assertOk();
    $response->assertDontSee(route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $termDate]), false);
});

test('the dashboard links its upcoming dates to the poll', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $rehearsal = make_term_date($term);
    $concert = make_term_date($term, $ensemble);

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $response = $this->actingAs($member)->get('/dashboard');

    $response->assertOk();
    $response->assertSee(route('attendance.poll', ['ensemble' => $ensemble, 'term' => $term]), false);
    // A member cannot take a register, so no register link is offered.
    $response->assertDontSee(route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $rehearsal]), false);

    $moderator = make_user(UserRole::Moderator);
    join_ensemble($moderator, $ensemble);

    $moderatorResponse = $this->actingAs($moderator)->get('/dashboard');

    $moderatorResponse->assertOk();
    $moderatorResponse->assertSee(route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $rehearsal]), false);
    $moderatorResponse->assertSee(route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $concert]), false);
});

test('an ensemble page links its next rehearsal and concert to the poll and register', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $term = Term::factory()->create();

    $rehearsal = make_term_date($term);
    $concert = make_term_date($term, $ensemble);
    $otherConcert = make_term_date($term, $otherEnsemble);

    $moderator = make_user(UserRole::Moderator);

    $response = $this->actingAs($moderator)->get(route('ensembles.show', $ensemble));

    $response->assertOk();
    $response->assertSee(route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $rehearsal]), false);
    $response->assertSee(route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $concert]), false);
    $response->assertDontSee(route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $otherConcert]), false);
});
