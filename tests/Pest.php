<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(
    TestCase::class,
    RefreshDatabase::class,
)->in('Feature', 'Unit');

// Browser tests drive a real browser, so they need the built assets (`npm run build`).
uses(
    TestCase::class,
    RefreshDatabase::class,
)->beforeEach(function () {
    $this->withVite();
})->in('Browser');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function make_user(UserRole $role = UserRole::Member, array $attributes = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attributes));
}

function make_instrument_family(string $name = 'Test Family'): InstrumentFamily
{
    return InstrumentFamily::firstOrCreate(['name' => $name]);
}

function join_ensemble(User $user, Ensemble $ensemble, ?InstrumentFamily $instrumentFamily = null, int|string|null $seatRow = null, int|string|null $seatColumn = null): void
{
    $user->ensembles()->attach($ensemble->id, [
        'instrument_family_id' => $instrumentFamily?->id ?? make_instrument_family()->id,
        'seat_row' => $seatRow,
        'seat_column' => $seatColumn,
    ]);
}

/**
 * A term date: a rehearsal by default, or that ensemble's concert when one is
 * given. `term_id` is not fillable, hence the forceCreate().
 */
function make_term_date(?Term $term = null, ?Ensemble $concertEnsemble = null): TermDate
{
    return TermDate::forceCreate([
        'term_id' => ($term ?? Term::factory()->create())->id,
        'start_datetime' => now()->addDay()->setTime(19, 0),
        'end_datetime' => now()->addDay()->setTime(21, 0),
        'concert_ensemble_id' => $concertEnsemble?->id,
    ]);
}

function join_ensemble_without_instrument(User $user, Ensemble $ensemble): void
{
    $user->ensembles()->attach($ensemble->id, [
        'instrument_family_id' => null,
        'seat_row' => null,
        'seat_column' => null,
    ]);
}
