<?php

use App\Enums\UserRole;
use App\Models\Ensemble;

test('the users relation excludes generic ensemble logins', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    $ensembleLogin = make_user(UserRole::Ensemble);
    join_ensemble($member, $ensemble);
    join_ensemble($ensembleLogin, $ensemble);

    expect($ensemble->users->pluck('id')->all())->toBe([$member->id]);
});

test('the users relation is ordered by first name', function () {
    $ensemble = Ensemble::factory()->create();
    $zoe = make_user(UserRole::Member, ['first_name' => 'Zoe']);
    $alice = make_user(UserRole::Member, ['first_name' => 'Alice']);
    join_ensemble($zoe, $ensemble);
    join_ensemble($alice, $ensemble);

    expect($ensemble->users->pluck('first_name')->all())->toBe(['Alice', 'Zoe']);
});

test('admins are related through the ensemble_admins table and ordered by first name', function () {
    $ensemble = Ensemble::factory()->create();
    $walter = make_user(UserRole::Admin, ['first_name' => 'Walter']);
    $brian = make_user(UserRole::Admin, ['first_name' => 'Brian']);
    $ensemble->admins()->attach([$walter->id, $brian->id]);

    expect($ensemble->admins->pluck('first_name')->all())->toBe(['Brian', 'Walter']);
});

test('ensembles are soft deleted and can be restored', function () {
    $ensemble = Ensemble::factory()->create();

    $ensemble->delete();
    expect(Ensemble::find($ensemble->id))->toBeNull();

    $ensemble->restore();
    expect(Ensemble::find($ensemble->id))->not->toBeNull();
});
