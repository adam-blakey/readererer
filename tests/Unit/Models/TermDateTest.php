<?php

use App\Features\FairVanDriverRota;
use App\Models\Ensemble;
use App\Models\Location;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;
use Laravel\Pennant\Feature;

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

test('labels use 12-hour times when the site is set to them', function () {
    config(['app.readererer_twenty_four_hour_clock' => false]);

    expect(term_date_at('2026-01-05 09:00:00', '2026-01-05 21:30:00')->schedule_label)->toBe('Mon, 5 Jan 2026, 9:00am–9:30pm');
});

test('labels show TBC in place of the time when it is not confirmed', function () {
    $termDate = term_date_at('2026-01-05 19:00:00', '2026-01-05 21:30:00', ['time_tbc' => true]);

    expect($termDate->name)->toBe('Monday, 5th January 2026, time TBC')
        ->and($termDate->time_label)->toBe('TBC')
        ->and($termDate->schedule_label)->toBe('Mon, 5 Jan 2026, TBC');

    $multiDay = term_date_at('2026-01-05 19:00:00', '2026-01-06 21:30:00', ['time_tbc' => true]);

    expect($multiDay->name)->toBe('Monday, 5th January 2026 – Tuesday, 6th January 2026, time TBC')
        ->and($multiDay->schedule_label)->toBe('Mon, 5 Jan 2026 – Tue, 6 Jan 2026, TBC');
});

test('location label is the location name, or TBC without one', function () {
    $termDate = term_date_at('2026-01-05 19:00:00', '2026-01-05 21:30:00');

    expect($termDate->location_label)->toBe('TBC');

    $termDate->update(['location_id' => Location::factory()->create(['name' => 'Town Hall'])->id]);

    expect($termDate->fresh()->location_label)->toBe('Town Hall');
});

test('a date without a location inherits the term\'s default', function () {
    $termDate = term_date_at('2026-01-05 19:00:00', '2026-01-05 21:30:00');
    $termDate->term->update(['location_id' => Location::factory()->create(['name' => 'Term Hall'])->id]);

    expect($termDate->fresh()->location_label)->toBe('Term Hall');

    $termDate->update(['location_id' => Location::factory()->create(['name' => 'Concert Hall'])->id]);

    expect($termDate->fresh()->location_label)->toBe('Concert Hall');
});

test('a date whose time is TBC is saved at midnight, so reminders count from the start of the day', function () {
    config(['app.readererer_reminder_minutes' => 24 * 60]);
    $termDate = term_date_at('2026-01-05 19:00:00', '2026-01-06 21:30:00', ['time_tbc' => true]);

    expect($termDate->start_datetime->format('Y-m-d H:i'))->toBe('2026-01-05 00:00')
        ->and($termDate->end_datetime->format('Y-m-d H:i'))->toBe('2026-01-06 00:00')
        ->and($termDate->remindersDueAt()->format('Y-m-d H:i'))->toBe('2026-01-04 00:00');
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

test('term dates belong to a term and optionally to concert ensembles', function () {
    $term = Term::factory()->create();
    $ensemble = Ensemble::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => '2026-01-05 19:00:00',
        'end_datetime' => '2026-01-05 21:00:00',
    ]);
    $termDate->concert_ensembles()->attach($ensemble);

    expect($termDate->term->is($term))->toBeTrue();
    expect($termDate->concert_ensembles->sole()->is($ensemble))->toBeTrue();
    expect($termDate->is_concert)->toBeTrue();
});

test('a concert with several ensembles applies to each of them and not to others', function () {
    [$wind, $brass, $strings] = Ensemble::factory()->count(3)->create()->all();
    $concert = make_term_date(null, $wind, $brass);
    $rehearsal = make_term_date();

    expect($concert->appliesToEnsemble($wind))->toBeTrue();
    expect($concert->appliesToEnsemble($brass))->toBeTrue();
    expect($concert->appliesToEnsemble($strings))->toBeFalse();
    expect($rehearsal->appliesToEnsemble($strings))->toBeTrue();
    expect($rehearsal->is_concert)->toBeFalse();
    expect($concert->concert_ensemble_names)->toBe(collect([$wind, $brass])->sortBy('name')->pluck('name')->join(', '));
});

test('an explicitly assigned van driver wins over the rotation', function () {
    $setupGroup = SetupGroup::factory()->create();
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
    $setupGroup = SetupGroup::factory()->create();

    $termDate = term_date_at('2026-01-05 19:00:00', '2026-01-05 21:00:00', [
        'setup_group_id' => $setupGroup->id,
    ]);

    expect($termDate->inferred_van_driver)->toBeNull();
});

test('van drivers rotate across a setup group\'s term dates in start order', function () {
    $setupGroup = SetupGroup::factory()->create();
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

/**
 * @param  array<int, ?string>  $overrides  each date's chosen driver, null for none
 * @param  array<string, int>  $joinWeeks  drivers who join the group just before the given date, the rest are in it from the start
 * @return array<int, string> the name of each date's driver
 */
function fair_rota(array $groupDrivers, array $overrides, array $joinWeeks = []): array
{
    config(['app.readererer_feature_flags' => [FairVanDriverRota::class => ['enabled' => true]]]);
    Feature::flushCache();

    $setupGroup = SetupGroup::factory()->create();
    $week = fn (int $week) => now()->addWeeks($week + 1)->setTime(19, 0);
    $users = collect([...$groupDrivers, ...$overrides])->filter()->unique()
        ->mapWithKeys(fn (string $name) => [$name => User::factory()->create(['first_name' => $name, 'last_name' => ''])]);
    foreach ($groupDrivers as $sort => $name) {
        $setupGroup->van_drivers()->attach($users[$name]->id, ['sort' => $sort] + (isset($joinWeeks[$name]) ? ['created_at' => $week($joinWeeks[$name])->subDay()] : []));
    }

    $term = Term::factory()->create();

    return collect($overrides)
        ->map(fn (?string $override, int $index) => TermDate::forceCreate([
            'term_id' => $term->id,
            'start_datetime' => $week($index),
            'end_datetime' => $week($index)->addHours(2),
            'setup_group_id' => $setupGroup->id,
            'van_driver_id' => $override ? $users[$override]->id : null,
        ]))
        ->map(fn (TermDate $date) => trim($date->inferred_van_driver->name))
        ->all();
}

test('the fair rota takes turns', function () {
    expect(fair_rota(['A', 'B'], [null, null, null, null, null]))->toBe(['A', 'B', 'A', 'B', 'A']);
});

test('the fair rota gives a covered date back to the driver who missed it', function () {
    expect(fair_rota(['A', 'B'], [null, 'A', null, null, null, null]))->toBe(['A', 'A', 'B', 'B', 'A', 'B']);
});

test('the fair rota carries on past a date driven by someone outside the group', function () {
    expect(fair_rota(['A', 'B'], [null, 'C', null, null]))->toBe(['A', 'C', 'B', 'A']);
});

test('a driver joining the fair rota drives once and then takes turns', function () {
    expect(fair_rota(['A', 'B', 'C'], array_fill(0, 8, null), ['C' => 4]))
        ->toBe(['A', 'B', 'A', 'B', 'C', 'A', 'B', 'C']);
});
