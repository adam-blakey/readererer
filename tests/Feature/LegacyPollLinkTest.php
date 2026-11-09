<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Term;

test('an old Pollererer poll link redirects permanently to the poll', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'Wind Band', 'slug' => 'wind-band']);
    $term = Term::factory()->create(['name' => 'Autumn', 'slug' => 'autumn']);
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $this->actingAs($member)
        ->get('/wind-band/autumn')
        ->assertRedirect(route('attendance.poll.show', [$ensemble, $term]))
        ->assertStatus(301);
});

test('old poll links with an unknown ensemble or term slug are not found', function () {
    $ensemble = Ensemble::factory()->create(['slug' => 'wind-band']);
    Term::factory()->create(['slug' => 'autumn']);
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $this->actingAs($member)->get('/wind-band/spring')->assertNotFound();
    $this->get('/brass-band/autumn')->assertNotFound();
    $this->get('/no/such')->assertNotFound();
});

test('the old poll link does not shadow app routes', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->get('/terms/create')
        ->assertOk();
});
