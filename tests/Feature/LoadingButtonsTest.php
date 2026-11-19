<?php

use App\Enums\UserRole;
use App\Features\LoadingButtons;
use App\Models\Term;
use App\Models\TermDate;
use Laravel\Pennant\Feature;

function loading_buttons_flag(bool $on): void
{
    config(['app.readererer_feature_flags' => $on ? [LoadingButtons::class => ['enabled' => true]] : []]);
    Feature::flushCache();
}

function loading_buttons_page(): string
{
    $termDate = TermDate::forceCreate([
        'term_id' => Term::factory()->create()->id,
        'start_datetime' => '2026-06-01 19:00:00',
        'end_datetime' => '2026-06-01 21:00:00',
    ]);

    return route('term-dates.show', $termDate);
}

test('send buttons carry the loading text while the flag is on', function () {
    loading_buttons_flag(true);

    $this->actingAs(make_user(UserRole::Admin))->get(loading_buttons_page())->assertSee('data-loading-text="Sending…"', false);
});

test('send buttons have no loading text while the flag is off', function () {
    loading_buttons_flag(false);

    $this->actingAs(make_user(UserRole::Admin))->get(loading_buttons_page())->assertDontSee('data-loading-text', false);
});
