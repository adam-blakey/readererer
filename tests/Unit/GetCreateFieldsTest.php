<?php

use App\Enums\Color;
use App\Enums\UserRole;
use App\Models\EmailLog;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
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

test('a column with an enum cast becomes an enum field', function () {
    // users.role is an integer column; the UserRole cast is what makes it a select.
    $fields = get_create_fields(new User);

    expect($fields['role']['type'])->toBe('enum');
    expect($fields['role']['required'])->toBeTrue();
    expect($fields['role']['select_multiple'])->toBeFalse();
});

test('enum options are keyed by backing value and labelled by case name', function () {
    $fields = get_create_fields(new User);

    expect($fields['role']['options'])->toBe([
        0 => 'Guest',
        1 => 'Ensemble',
        2 => 'Member',
        3 => 'Moderator',
        4 => 'Admin',
    ]);
});

test('enum options use the enum label() method when it defines one', function () {
    $fields = get_create_fields(new SetupGroup);

    expect($fields['color']['type'])->toBe('enum');
    expect($fields['color']['options'])->toHaveCount(count(Color::cases()));
    expect($fields['color']['options']['teal'])->toBe(Color::Teal->label());
});

test('the enum default option comes from the database default', function () {
    // users.role defaults to UserRole::Member, instrument_families.color to 'blue'.
    expect(get_create_fields(new User)['role']['default_option'])->toBe(UserRole::Member->value);
    expect(get_create_fields(new InstrumentFamily)['color']['default_option'])->toBe(Color::Blue->value);
});

test('an enum column without a database default has no default option', function () {
    expect(get_create_fields(new SetupGroup)['color']['default_option'])->toBeNull();
});

test('enum casts are picked up from a $casts property as well as a casts() method', function () {
    // EmailLog declares its casts as a property rather than overriding casts().
    $fields = get_create_fields(new EmailLog);

    expect($fields['status']['type'])->toBe('enum');
    expect($fields['status']['options'])->toBe([
        0 => 'Pending',
        1 => 'Sent',
        2 => 'Failed',
    ]);
});

test('the value of an enum field is the model\'s current case', function () {
    $instrumentFamily = new InstrumentFamily(['name' => 'Bassoons', 'color' => Color::Teal]);

    expect(get_create_fields($instrumentFamily)['color']['value'])->toBe(Color::Teal);
});
