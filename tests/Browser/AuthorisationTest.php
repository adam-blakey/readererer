<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Term;

test('a member is not offered the admin pages and is refused them', function () {
    $this->actingAs(make_user(UserRole::Member));

    visit('/dashboard')
        ->assertNotPresent('a[href$="/ensembles"]')
        ->assertNotPresent('a[href$="/users"]');

    visit('/ensembles')
        ->assertSee('Forbidden')
        ->assertSee('You are not authorised to access this page.');
});

test('a shared ensemble login can open its own poll but not another ensemble\'s', function () {
    $ownEnsemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    make_term_date($term);

    $ensembleLogin = make_user(UserRole::Ensemble);
    join_ensemble($ensembleLogin, $ownEnsemble);

    $this->actingAs($ensembleLogin);

    visit(route('attendance.poll', ['ensemble' => $ownEnsemble, 'term' => $term], absolute: false))
        ->assertSee("{$ownEnsemble->name}: {$term->name}")
        ->assertDontSee('Forbidden');

    visit(route('attendance.poll', ['ensemble' => $otherEnsemble, 'term' => $term], absolute: false))
        ->assertSee('Forbidden');
});
