<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;

function make_term(string $name, string $start): Term
{
    $term = Term::factory()->create(['name' => $name]);
    TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => $start,
        'end_datetime' => now()->parse($start)->addHours(2),
    ]);

    return $term;
}

test('the home page lists each ensemble with its active polls', function () {
    Ensemble::factory()->create(['name' => 'Wind Band']);
    Ensemble::factory()->create(['name' => 'String Orchestra']);
    make_term('Spring term', now()->addWeek());
    make_term('Last year', now()->subYear());

    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder(['String Orchestra', 'Spring term', 'Wind Band', 'Spring term'])
        ->assertDontSee('Last year');
});

test('guests and users who can open them get links to each ensemble and poll', function (?UserRole $role) {
    $ensemble = Ensemble::factory()->create();
    $term = make_term('Spring term', now()->addWeek());

    if ($role) {
        $this->actingAs(make_user($role));
    }

    $this->get('/')
        ->assertSee(route('ensembles.show', $ensemble))
        ->assertSee(route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $term]));
})->with([null, UserRole::Admin]);

test('the home page does not link ensembles a member cannot open', function () {
    $ensemble = Ensemble::factory()->create();
    make_term('Spring term', now()->addWeek());

    $this->actingAs(make_user(UserRole::Member))
        ->get('/')
        ->assertSee($ensemble->name)
        ->assertDontSee(route('ensembles.show', $ensemble));
});
