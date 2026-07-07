<?php

use App\Models\Ensemble;
use App\Models\User;
use App\Models\UserEnsemble;

test('seat combines the seat column and row', function () {
    $ensemble = Ensemble::factory()->create();
    $user = User::factory()->create();
    join_ensemble($user, $ensemble, null, 3, 'B');

    $pivot = UserEnsemble::where('user_id', $user->id)->where('ensemble_id', $ensemble->id)->first();

    expect($pivot->seat)->toBe('B3');
});

test('the pivot exposes its user, ensemble and instrument family', function () {
    $ensemble = Ensemble::factory()->create();
    $user = User::factory()->create();
    $instrumentFamily = make_instrument_family('Brass');
    join_ensemble($user, $ensemble, $instrumentFamily);

    $pivot = UserEnsemble::where('user_id', $user->id)->where('ensemble_id', $ensemble->id)->first();

    expect($pivot->user->is($user))->toBeTrue();
    expect($pivot->ensemble->is($ensemble))->toBeTrue();
    expect($pivot->instrumentFamily->is($instrumentFamily))->toBeTrue();
});
