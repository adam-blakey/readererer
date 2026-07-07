<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\EnsembleAdmin;
use App\Models\InstrumentFamily;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function attach_member_to_ensemble(User $user, Ensemble $ensemble): void
{
    $instrumentFamilyId = InstrumentFamily::query()->first()?->id
        ?? InstrumentFamily::create(['name' => 'Test Family'])->id;

    $user->ensembles()->attach($ensemble->id, [
        'instrument_family_id' => $instrumentFamilyId,
        'seat_column' => null,
        'seat_row' => null,
    ]);
}

test('moderators and admins can view ensemble admins of any ensemble', function (UserRole $role) {
    $ensemble = Ensemble::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $user = User::factory()->create(['role' => $role]);
    $ensembleAdmin = new EnsembleAdmin(['ensemble_id' => $ensemble->id, 'admin_id' => $admin->id]);

    expect($user->can('view', $ensembleAdmin))->toBeTrue();
})->with([UserRole::Moderator, UserRole::Admin]);

test('members can view the admins of an ensemble they belong to', function () {
    $ensemble = Ensemble::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $user = User::factory()->create(['role' => UserRole::Member]);
    attach_member_to_ensemble($user, $ensemble);
    $ensembleAdmin = new EnsembleAdmin(['ensemble_id' => $ensemble->id, 'admin_id' => $admin->id]);

    expect($user->can('view', $ensembleAdmin))->toBeTrue();
});

test('members cannot view the admins of an ensemble they do not belong to', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $user = User::factory()->create(['role' => UserRole::Member]);
    attach_member_to_ensemble($user, $otherEnsemble);
    $ensembleAdmin = new EnsembleAdmin(['ensemble_id' => $ensemble->id, 'admin_id' => $admin->id]);

    expect($user->can('view', $ensembleAdmin))->toBeFalse();
});

test('users below the member role cannot view ensemble admins', function (UserRole $role) {
    $ensemble = Ensemble::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $user = User::factory()->create(['role' => $role]);
    attach_member_to_ensemble($user, $ensemble);
    $ensembleAdmin = new EnsembleAdmin(['ensemble_id' => $ensemble->id, 'admin_id' => $admin->id]);

    expect($user->can('view', $ensembleAdmin))->toBeFalse();
})->with([UserRole::Guest, UserRole::Ensemble]);
