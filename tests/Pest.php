<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class,
)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every test file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

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

function join_ensemble_without_instrument(User $user, Ensemble $ensemble): void
{
    $user->ensembles()->attach($ensemble->id, [
        'instrument_family_id' => null,
        'seat_row' => null,
        'seat_column' => null,
    ]);
}
