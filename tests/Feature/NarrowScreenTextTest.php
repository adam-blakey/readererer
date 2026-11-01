<?php

use App\Models\Ensemble;
use App\Models\Term;

test('a member entry keeps its name and secondary line at narrow widths', function () {
    $member = make_user();

    $rendered = $this->blade(
        '<x-user-entry :user="$user" :add_route="false" secondary_info="Violin" />',
        ['user' => $member],
    );

    $rendered->assertDontSee('d-xl-block', false);
    $rendered->assertSee('text-truncate', false);
    $rendered->assertSee($member->name);
    $rendered->assertSee('Violin');
});

test('a poll entry keeps the term name and dates at narrow widths', function () {
    $term = Term::factory()->create();
    $ensemble = Ensemble::factory()->create();

    $rendered = $this->blade(
        '<x-poll-entry :ensemble="$ensemble" :term="$term" />',
        ['ensemble' => $ensemble, 'term' => $term],
    );

    $rendered->assertDontSee('d-xl-block', false);
    $rendered->assertSee($term->name);
    $rendered->assertSee($term->FormattedTermDateRange);
});

test('the navbar name shows from the small breakpoint up rather than only on extra large screens', function () {
    $user = make_user();

    $rendered = $this->blade('<x-name-and-role :user="$user" />', ['user' => $user]);

    $rendered->assertDontSee('d-xl-block', false);
    $rendered->assertSee('d-sm-block', false);
    $rendered->assertSee($user->name);
});
