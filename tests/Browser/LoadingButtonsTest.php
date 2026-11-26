<?php

use App\Enums\UserRole;
use App\Models\Term;
use App\Models\TermDate;

test('a send button is disabled and shows a spinner once its form is submitted', function () {

    $termDate = TermDate::forceCreate([
        'term_id' => Term::factory()->create()->id,
        'start_datetime' => '2026-06-01 19:00:00',
        'end_datetime' => '2026-06-01 21:00:00',
    ]);

    $this->actingAs(make_user(UserRole::Admin));

    $page = visit(route('term-dates.show', $termDate, absolute: false))->assertNotPresent('[data-loading-text][aria-busy]');
    $page->script("window.addEventListener('submit', (event) => event.preventDefault())");
    $page
        ->click('[data-loading-text]')
        ->assertPresent('[data-loading-text][aria-busy="true"][disabled] .spinner-border');
});
