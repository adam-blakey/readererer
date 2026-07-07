<?php

use App\Models\Ensemble;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\User;

test('a plain string column becomes a required text field', function () {
    $fields = get_create_fields(new Ensemble);

    expect($fields)->toHaveKey('name');
    expect($fields['name']['label'])->toBe('Name');
    expect($fields['name']['type'])->toBe('text');
    expect($fields['name']['required'])->toBeTrue();
    expect($fields['name']['select_multiple'])->toBeFalse();
});

test('fillable entries without a database column or relation are skipped', function () {
    // Term lists 'term_dates' in $fillable but it is a HasMany relation, not a column.
    $fields = get_create_fields(new Term);

    expect($fields)->toHaveKeys(['name', 'slug']);
    expect($fields)->not->toHaveKey('term_dates');
});

test('a belongs-to-many relation becomes an optional multi-select class field', function () {
    $drivers = User::factory()->count(2)->create();

    $fields = get_create_fields(new SetupGroup);

    expect($fields)->toHaveKey('van_drivers');
    expect($fields['van_drivers']['type'])->toBe('class');
    expect($fields['van_drivers']['required'])->toBeFalse();
    expect($fields['van_drivers']['select_multiple'])->toBeTrue();
    expect($fields['van_drivers']['options'])->toHaveCount(2);
});

test('icons come from the model Icon attributes with a pencil fallback', function () {
    $fields = get_create_fields(new SetupGroup);

    expect($fields['name']['icon'])->toBe('arrow-badge-right');
    expect($fields['week']['icon'])->toBe('calendar');
    expect($fields['color']['icon'])->toBe('paint');
    expect($fields['van_drivers']['icon'])->toBe('truck');

    // Ensemble does not use HasPropertyIcons, so everything falls back to pencil.
    $ensembleFields = get_create_fields(new Ensemble);
    expect($ensembleFields['name']['icon'])->toBe('pencil');
});

test('column labels are humanised', function () {
    $fields = get_create_fields(new User);

    expect($fields['first_name']['label'])->toBe('First name');
    expect($fields['last_name']['label'])->toBe('Last name');
});

test('an email column becomes an email field', function () {
    $fields = get_create_fields(new User);

    expect($fields['email']['type'])->toBe('email');
});

test('an integer column becomes a number field', function () {
    $fields = get_create_fields(new SetupGroup);

    expect($fields['week']['type'])->toBe('number');
});
