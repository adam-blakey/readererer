<?php

use App\Enums\UserRole;
use App\Models\Term;
use App\Models\TermDate;

function loading_buttons_page(): string
{
    $termDate = TermDate::forceCreate([
        'term_id' => Term::factory()->create()->id,
        'start_datetime' => '2026-06-01 19:00:00',
        'end_datetime' => '2026-06-01 21:00:00',
    ]);

    return route('term-dates.show', $termDate);
}

test('send buttons carry the loading text', function () {
    $this->actingAs(make_user(UserRole::Admin))->get(loading_buttons_page())->assertSee('data-loading-text="Sending…"', false);
});
