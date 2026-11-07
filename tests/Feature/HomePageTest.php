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
        ->assertDontSee('Last year')
        ->assertDontSee(route('attendance.poll.show', ['ensemble' => Ensemble::first(), 'term' => Term::first()]));
});

test('the home page links polls for users who can open them', function () {
    $ensemble = Ensemble::factory()->create();
    $term = make_term('Spring term', now()->addWeek());

    $this->actingAs(make_user(UserRole::Admin))
        ->get('/')
        ->assertSee(route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $term]));
});
