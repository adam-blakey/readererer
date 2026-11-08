<?php

use App\Enums\UserRole;
use App\Features\UnsavedChangesWarning;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\Term;
use Laravel\Pennant\Feature;

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

    $this->actingAs(make_user(UserRole::Admin))
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
        ->get(route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]))
        ->assertOk()
        ->assertSee('data-dirty-check', false);

    $this->actingAs($moderator)
        ->get(route('attendance.register.show', ['ensemble' => $ensemble->slug, 'termDate' => $termDate->id]))
        ->assertOk()
        ->assertSee('data-dirty-check', false);
});

test('pages warn about unsaved changes only for signed-in users with the flag on', function () {
    $this->get(route('login'))->assertOk()->assertDontSee('data-warn-unsaved', false);
    $this->actingAs(make_user(UserRole::Member))->get(route('dashboard'))->assertOk()->assertDontSee('data-warn-unsaved', false);

    config(['app.readererer_feature_flags' => [UnsavedChangesWarning::class => ['enabled' => true]]]);
    Feature::flushCache();

    $this->actingAs(make_user(UserRole::Member))->get(route('dashboard'))->assertOk()->assertSee('data-warn-unsaved', false);

    auth()->logout();
    $this->get(route('login'))->assertOk()->assertDontSee('data-warn-unsaved', false);
});
