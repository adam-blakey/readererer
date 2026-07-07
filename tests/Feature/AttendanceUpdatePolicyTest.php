<?php

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\User;

test('moderators and admins can update attendance for any ensemble', function (UserRole $role) {
    $ensemble = Ensemble::factory()->create();
    $user = User::factory()->create(['role' => $role]);
    $attendance = new Attendance(['ensemble_id' => $ensemble->id]);

    expect($user->can('update', $attendance))->toBeTrue();
})->with([UserRole::Moderator, UserRole::Admin]);

test('ensemble and member users can update attendance for an ensemble they belong to', function (UserRole $role) {
    $ensemble = Ensemble::factory()->create();
    $user = User::factory()->create(['role' => $role]);
    join_ensemble($user, $ensemble);
    $attendance = new Attendance(['ensemble_id' => $ensemble->id]);

    expect($user->can('update', $attendance))->toBeTrue();
})->with([UserRole::Ensemble, UserRole::Member]);

test('ensemble and member users cannot update attendance for an ensemble they do not belong to', function (UserRole $role) {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $user = User::factory()->create(['role' => $role]);
    join_ensemble($user, $otherEnsemble);
    $attendance = new Attendance(['ensemble_id' => $ensemble->id]);

    expect($user->can('update', $attendance))->toBeFalse();
})->with([UserRole::Ensemble, UserRole::Member]);

test('guests cannot update attendance even for an ensemble they belong to', function () {
    $ensemble = Ensemble::factory()->create();
    $user = User::factory()->create(['role' => UserRole::Guest]);
    join_ensemble($user, $ensemble);
    $attendance = new Attendance(['ensemble_id' => $ensemble->id]);

    expect($user->can('update', $attendance))->toBeFalse();
});
