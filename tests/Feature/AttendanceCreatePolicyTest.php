<?php

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\User;

test('moderators and admins can reply to the poll for any ensemble', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    expect($user->can('create', [Attendance::class, Ensemble::factory()->create()]))->toBeTrue();
})->with([UserRole::Moderator, UserRole::Admin]);

test('ensemble and member users can reply to the poll for an ensemble they belong to', function (UserRole $role) {
    $ensemble = Ensemble::factory()->create();
    $user = User::factory()->create(['role' => $role]);
    join_ensemble($user, $ensemble);

    expect($user->can('create', [Attendance::class, $ensemble]))->toBeTrue();
})->with([UserRole::Ensemble, UserRole::Member]);

test('ensemble and member users cannot reply to the poll for an ensemble they do not belong to', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    join_ensemble($user, Ensemble::factory()->create());

    expect($user->can('create', [Attendance::class, Ensemble::factory()->create()]))->toBeFalse()
        ->and($user->can('create', Attendance::class))->toBeFalse();
})->with([UserRole::Ensemble, UserRole::Member]);

test('guests cannot reply to the poll even for an ensemble they belong to', function () {
    $ensemble = Ensemble::factory()->create();
    $user = User::factory()->create(['role' => UserRole::Guest]);
    join_ensemble($user, $ensemble);

    expect($user->can('create', [Attendance::class, $ensemble]))->toBeFalse();
});
