<?php

use App\Models\Ensemble;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;

function make_setup_group(array $attributes = []): SetupGroup
{
    return SetupGroup::create(array_merge([
        'name' => 'Group A',
        'week' => 1,
        'color' => 'blue',
    ], $attributes));
}

function term_date_at(string $start, string $end, array $attributes = []): TermDate
{
    return TermDate::forceCreate(array_merge([
        'term_id' => Term::factory()->create()->id,
        'start_datetime' => $start,
        'end_datetime' => $end,
    ], $attributes));
}

function term_date_days_away(int $days): TermDate
{
    $day = now()->addDays($days);

    return term_date_at($day->format('Y-m-d').' 19:00:00', $day->format('Y-m-d').' 21:00:00');
}

test('name shows one date with a time range when start and end are on the same day', function () {
    $termDate = term_date_at('2026-01-05 19:00:00', '2026-01-05 21:30:00');

    expect($termDate->name)->toBe('Monday, 5th January 2026, 19:00–21:30');
});

test('name shows both datetimes when start and end are on different days', function () {
    $termDate = term_date_at('2026-01-05 19:00:00', '2026-01-06 21:30:00');

    expect($termDate->name)->toBe('Monday, 5th January 2026, 19:00 – Tuesday, 6th January 2026, 21:30');
});

test('schedule label puts a single day\'s date and time range on one line', function () {
    $termDate = term_date_at('2026-01-05 19:00:00', '2026-01-05 21:30:00');

    expect($termDate->schedule_label)->toBe('Mon, 5 Jan 2026, 19:00–21:30');
});

test('schedule label spells out both ends of a multi-day date', function () {
    $termDate = term_date_at('2026-01-05 19:00:00', '2026-01-06 21:30:00');

    expect($termDate->schedule_label)->toBe('Mon, 5 Jan 2026, 19:00 – Tue, 6 Jan 2026, 21:30');
});

test('relative label names today, tomorrow and yesterday', function () {
    expect(term_date_days_away(0)->relative_label)->toBe('Today');
    expect(term_date_days_away(1)->relative_label)->toBe('Tomorrow');
    expect(term_date_days_away(-1)->relative_label)->toBe('Yesterday');
});

test('relative label counts days for dates within the next fortnight', function () {
    expect(term_date_days_away(5)->relative_label)->toBe('In 5 days');
});

test('relative label counts days back for recent past dates', function () {
    expect(term_date_days_away(-3)->relative_label)->toBe('3 days ago');
});

test('relative label falls back to a coarser description for distant dates', function () {
    $this->travelTo('2026-01-01 12:00:00');
    $termDate = term_date_at('2026-04-01 19:00:00', '2026-04-01 21:00:00');

    expect($termDate->relative_label)->toBe('3 months from now');
});

test('term dates belong to a term and optionally to a concert ensemble', function () {
    $term = Term::factory()->create();
    $ensemble = Ensemble::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => '2026-01-05 19:00:00',
        'end_datetime' => '2026-01-05 21:00:00',
        'concert_ensemble_id' => $ensemble->id,
    ]);

    expect($termDate->term->is($term))->toBeTrue();
    expect($termDate->concert_ensemble->is($ensemble))->toBeTrue();
});

test('an explicitly assigned van driver wins over the rotation', function () {
    $setupGroup = make_setup_group();
    $explicitDriver = User::factory()->create();
    $rotationDriver = User::factory()->create();
    $setupGroup->van_drivers()->attach($rotationDriver->id, ['sort' => 0]);

    $termDate = term_date_at('2026-01-05 19:00:00', '2026-01-05 21:00:00', [
        'setup_group_id' => $setupGroup->id,
        'van_driver_id' => $explicitDriver->id,
    ]);

    expect($termDate->inferred_van_driver->is($explicitDriver))->toBeTrue();
});

test('there is no inferred van driver without a setup group', function () {
    $termDate = term_date_at('2026-01-05 19:00:00', '2026-01-05 21:00:00');

    expect($termDate->inferred_van_driver)->toBeNull();
});

test('there is no inferred van driver when the setup group has no van drivers', function () {
    $setupGroup = make_setup_group();

    $termDate = term_date_at('2026-01-05 19:00:00', '2026-01-05 21:00:00', [
        'setup_group_id' => $setupGroup->id,
    ]);

    expect($termDate->inferred_van_driver)->toBeNull();
});

test('van drivers rotate across a setup group\'s term dates in start order', function () {
    $setupGroup = make_setup_group();
    $driverOne = User::factory()->create();
    $driverTwo = User::factory()->create();
    $setupGroup->van_drivers()->attach($driverOne->id, ['sort' => 0]);
    $setupGroup->van_drivers()->attach($driverTwo->id, ['sort' => 1]);

    $term = Term::factory()->create();
    $dates = collect(['2026-01-05', '2026-01-12', '2026-01-19'])->map(
        fn (string $day) => TermDate::forceCreate([
            'term_id' => $term->id,
            'start_datetime' => "$day 19:00:00",
            'end_datetime' => "$day 21:00:00",
            'setup_group_id' => $setupGroup->id,
        ])
    );

    // The rotation is based on how many of the group's dates precede each one.
    expect($dates[0]->inferred_van_driver->is($driverOne))->toBeTrue();
    expect($dates[1]->inferred_van_driver->is($driverTwo))->toBeTrue();
    expect($dates[2]->inferred_van_driver->is($driverOne))->toBeTrue();
});
