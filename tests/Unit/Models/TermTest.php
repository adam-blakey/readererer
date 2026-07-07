<?php

use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;

function add_term_date(Term $term, string $start, string $end, ?int $concertEnsembleId = null): TermDate
{
    return TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => $start,
        'end_datetime' => $end,
        'concert_ensemble_id' => $concertEnsembleId,
    ]);
}

test('term dates with no concert ensemble are rehearsals, the rest are concerts', function () {
    $term = Term::factory()->create();
    $ensemble = Ensemble::factory()->create();

    add_term_date($term, '2026-01-05 19:00:00', '2026-01-05 21:00:00');
    add_term_date($term, '2026-01-12 19:00:00', '2026-01-12 21:00:00');
    add_term_date($term, '2026-03-20 19:30:00', '2026-03-20 22:00:00', $ensemble->id);

    expect($term->number_of_rehearsals)->toBe(2);
    expect($term->number_of_concerts)->toBe(1);
    expect($term->number_of_term_dates)->toBe(3);
});

test('earliest date is the start of the first term date', function () {
    $term = Term::factory()->create();

    add_term_date($term, '2026-01-12 19:00:00', '2026-01-12 21:00:00');
    add_term_date($term, '2026-01-05 19:00:00', '2026-01-05 21:00:00');

    expect($term->earliest_date->format('Y-m-d'))->toBe('2026-01-05');
});

test('earliest and latest dates are null for a term with no dates', function () {
    $term = Term::factory()->create();

    expect($term->earliest_date)->toBeNull();
    expect($term->latest_date)->toBeNull();
});

test('latest date is the start of the last term date', function () {
    $term = Term::factory()->create();

    add_term_date($term, '2026-03-20 19:30:00', '2026-03-20 22:00:00');
    add_term_date($term, '2026-01-05 19:00:00', '2026-01-05 21:00:00');

    expect($term->latest_date->format('Y-m-d'))->toBe('2026-03-20');
});

test('formatted term date range describes multi-month terms', function () {
    $term = Term::factory()->create();

    add_term_date($term, '2026-01-05 19:00:00', '2026-01-05 21:00:00');
    add_term_date($term, '2026-03-20 19:30:00', '2026-03-20 22:00:00');

    expect($term->formatted_term_date_range)->toBe('about 2 months, 2026-01-05 to 2026-03-20');
});

test('latest date equals the only date for a single-date term', function () {
    $term = Term::factory()->create();

    add_term_date($term, '2026-01-05 19:00:00', '2026-01-05 21:00:00');

    expect($term->latest_date->format('Y-m-d'))->toBe('2026-01-05');
});

test('formatted term date range is a dash for a term with no dates', function () {
    $term = Term::factory()->create();

    expect($term->formatted_term_date_range)->toBe('–');
});

test('formatted term date range shows a single date for a single-date term', function () {
    $term = Term::factory()->create();

    add_term_date($term, '2026-01-05 19:00:00', '2026-01-05 21:00:00');

    expect($term->formatted_term_date_range)->toBe('2026-01-05');
});

test('terms are soft deleted and can be restored', function () {
    $term = Term::factory()->create();

    $term->delete();
    expect(Term::find($term->id))->toBeNull();

    $term->restore();
    expect(Term::find($term->id))->not->toBeNull();
});

test('term dates are ordered by start datetime', function () {
    $term = Term::factory()->create();

    add_term_date($term, '2026-01-12 19:00:00', '2026-01-12 21:00:00');
    add_term_date($term, '2026-01-05 19:00:00', '2026-01-05 21:00:00');

    expect($term->term_dates->pluck('start_datetime')->map->format('Y-m-d')->all())
        ->toBe(['2026-01-05', '2026-01-12']);
});
