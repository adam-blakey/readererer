<?php

use App\Enums\UserRole;
use App\Features\CompactTermForm;
use App\Models\Term;
use Laravel\Pennant\Feature;

test('repeating dates are added to the term form and a removed date is deleted on save', function () {
    config(['app.readererer_feature_flags' => [CompactTermForm::class => ['enabled' => true]]]);
    Feature::flushCache();

    $term = Term::factory()->create();
    make_term_date($term);

    $this->actingAs(make_user(UserRole::Admin));

    visit(route('terms.edit', $term, absolute: false))
        ->assertCount('#term-dates-list .term-date-row', 1)
        ->assertNotPresent('#term-dates-list .term-date-body.show')
        ->click('button[aria-controls="repeat-dates-panel"]')
        ->fill('#repeat-first', '2027-01-06')
        ->fill('#repeat-last', '2027-01-20')
        ->fill('#repeat-start', '19:30')
        ->fill('#repeat-end', '21:30')
        ->click('#repeat-dates-panel button[type="submit"]')
        ->assertCount('#term-dates-list .term-date-row', 4)
        ->assertSee('Wed, 13 Jan 2027, 19:30–21:30')
        ->click('#term-dates-list .remove-term-date')
        ->assertSee('Removed when you save')
        ->click('button[form="term-edit-form"]')
        ->assertPathIs(route('terms.show', $term, absolute: false))
        ->assertNoJavaScriptErrors();

    expect($term->term_dates()->orderBy('start_datetime')->get()->map(fn ($date) => $date->start_datetime->format('Y-m-d H:i'))->all())
        ->toBe(['2027-01-06 19:30', '2027-01-13 19:30', '2027-01-20 19:30']);
});
