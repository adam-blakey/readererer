<?php

use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\User;
use App\Models\UserEnsemble;

test('membership describes the seat and instrument family', function () {
    $ensemble = Ensemble::factory()->create();
    $instrumentFamily = InstrumentFamily::create(['name' => 'Strings']);
    $user = User::factory()->create();
    $user->ensembles()->attach($ensemble->id, [
        'instrument_family_id' => $instrumentFamily->id,
        'seat_row' => 1,
        'seat_column' => 'A',
    ]);

    expect($user->membership($ensemble))->toBe('1A in Strings');
});

test('membership describes the instrument family when no seat is assigned', function () {
    $ensemble = Ensemble::factory()->create();
    $instrumentFamily = InstrumentFamily::create(['name' => 'Brass']);
    $user = User::factory()->create();
    $user->ensembles()->attach($ensemble->id, [
        'instrument_family_id' => $instrumentFamily->id,
        'seat_row' => null,
        'seat_column' => null,
    ]);

    expect($user->membership($ensemble))->toBe('Brass');
});

test('membership reports non-members instead of erroring', function () {
    $ensemble = Ensemble::factory()->create();
    $user = User::factory()->create();

    expect($user->membership($ensemble))->toBe('Not a member');
});

test('the ensembles pivot uses the UserEnsemble model', function () {
    $ensemble = Ensemble::factory()->create();
    $instrumentFamily = InstrumentFamily::create(['name' => 'Woodwind']);
    $user = User::factory()->create();
    $user->ensembles()->attach($ensemble->id, [
        'instrument_family_id' => $instrumentFamily->id,
        'seat_row' => null,
        'seat_column' => null,
    ]);

    expect($user->ensembles->first()->pivot)->toBeInstanceOf(UserEnsemble::class);
});
