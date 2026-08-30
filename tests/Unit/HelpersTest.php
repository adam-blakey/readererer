<?php

use App\Enums\AttendanceStatus;
use App\Enums\Color;
use App\Enums\UserRole;
use App\Models\Composer;
use App\Models\Ensemble;
use App\Models\SetupGroup;
use App\Models\Term;

// get_route_name_from_model / get_class_name_from_model

test('get_route_name_from_model builds the show route name by default', function () {
    expect(get_route_name_from_model(new Composer))->toBe('composers.show');
});

test('get_route_name_from_model builds other route names when asked', function () {
    expect(get_route_name_from_model(new Term, 'edit'))->toBe('terms.edit');
    expect(get_route_name_from_model(new Ensemble, 'index'))->toBe('ensembles.index');
});

test('get_class_name_from_model returns the lowercased base class name', function () {
    expect(get_class_name_from_model(new Composer))->toBe('composer');
    expect(get_class_name_from_model(new SetupGroup))->toBe('setupgroup');
});

// clean_attribute_name

test('clean_attribute_name replaces underscores and capitalises the first word', function () {
    expect(clean_attribute_name('first_name'))->toBe('First name');
    expect(clean_attribute_name('name'))->toBe('Name');
    expect(clean_attribute_name('emergency_contact_number'))->toBe('Emergency contact number');
});

// column_label

test('column_label uses the model\'s column_labels map when the attribute is listed', function () {
    $term = new Term;

    expect(column_label($term, 'number_of_rehearsals'))->toBe('Rehearsals');
    expect(column_label($term, 'number_of_concerts'))->toBe('Concerts');
    expect(column_label($term, 'earliest_date'))->toBe('First date');
    expect(column_label($term, 'latest_date'))->toBe('Last date');
});

test('column_label falls back to the cleaned attribute name for unmapped attributes', function () {
    expect(column_label(new Term, 'name'))->toBe('Name');
    expect(column_label(new Term, 'created_at'))->toBe('Created at');
});

test('column_label falls back for models without a column_labels map', function () {
    expect(column_label(new Composer, 'first_name'))->toBe('First name');
});

// call_or_default

test('call_or_default calls the method when it exists', function () {
    $setupGroup = new SetupGroup;

    expect(call_or_default($setupGroup, 'getIconForAttribute', 'week', 'pencil'))->toBe('calendar');
});

test('call_or_default falls back to the default when the method does not exist', function () {
    $composer = new Composer;

    expect(call_or_default($composer, 'getIconForAttribute', 'name', 'pencil'))->toBe('pencil');
});

test('call_or_default falls back to the default when the method returns null', function () {
    $setupGroup = new SetupGroup;

    expect(call_or_default($setupGroup, 'getIconForAttribute', 'no_such_attribute', 'pencil'))->toBe('pencil');
});

// map_database_type_to_html

test('map_database_type_to_html ignores casts that are not enums and falls back to the database type', function () {
    expect(map_database_type_to_html('status', 'integer', ['status' => 'boolean']))->toBe('number');
    expect(map_database_type_to_html('status', 'varchar', ['other_column' => AttendanceStatus::class]))->toBe('text');
});

test('map_database_type_to_html maps a column with an enum cast to an enum field', function () {
    expect(map_database_type_to_html('status', 'integer', ['status' => AttendanceStatus::class]))->toBe('enum');
    expect(map_database_type_to_html('color', 'varchar', ['color' => Color::class]))->toBe('enum');
});

test('an enum cast beats the image and email column name special cases', function () {
    expect(map_database_type_to_html('email', 'varchar', ['email' => Color::class]))->toBe('enum');
});

test('map_database_type_to_html special-cases image, email and password column names', function () {
    expect(map_database_type_to_html('image', 'varchar', []))->toBe('image');
    expect(map_database_type_to_html('email', 'varchar', []))->toBe('email');
    expect(map_database_type_to_html('password', 'varchar', []))->toBe('password');
});

test('map_database_type_to_html maps database types to html input types', function (string $dbType, string $expected) {
    expect(map_database_type_to_html('some_column', $dbType, []))->toBe($expected);
})->with([
    ['text', 'textarea'],
    ['longtext', 'textarea'],
    ['mediumtext', 'textarea'],
    ['integer', 'number'],
    ['bigint', 'number'],
    ['smallint', 'number'],
    ['decimal', 'number'],
    ['float', 'number'],
    ['boolean', 'boolean'],
    ['tinyint', 'boolean'],
    ['date', 'date'],
    ['datetime', 'datetime'],
    ['timestamp', 'datetime'],
    ['varchar', 'text'],
    ['VARCHAR', 'text'],
]);

// get_enum_class_for_attribute / get_enum_options / enum_case_label / get_enum_default

test('get_enum_class_for_attribute returns the enum class behind an enum cast', function () {
    expect(get_enum_class_for_attribute(['color' => Color::class], 'color'))->toBe(Color::class);
});

test('get_enum_class_for_attribute returns null for missing and non-enum casts', function () {
    expect(get_enum_class_for_attribute([], 'color'))->toBeNull();
    expect(get_enum_class_for_attribute(['created_at' => 'datetime'], 'created_at'))->toBeNull();
    expect(get_enum_class_for_attribute(['name' => 'App\Models\Composer'], 'name'))->toBeNull();
});

test('get_enum_options keys the options by backing value', function () {
    expect(get_enum_options(UserRole::class))->toBe([
        0 => 'Guest',
        1 => 'Ensemble',
        2 => 'Member',
        3 => 'Moderator',
        4 => 'Admin',
    ]);
});

test('enum_case_label humanises multi-word case names', function () {
    expect(enum_case_label(AttendanceStatus::NotAttending))->toBe('Not attending');
    expect(enum_case_label(AttendanceStatus::Unknown))->toBe('Unknown');
});

test('enum_case_label prefers the enum\'s own label method', function () {
    expect(enum_case_label(Color::Teal))->toBe(Color::Teal->label());
});

test('get_enum_default resolves a database default to the matching case value', function () {
    expect(get_enum_default(UserRole::class, '2'))->toBe(UserRole::Member->value);
    expect(get_enum_default(Color::class, 'teal'))->toBe(Color::Teal->value);
});

test('get_enum_default unquotes string defaults', function () {
    expect(get_enum_default(Color::class, "'teal'"))->toBe(Color::Teal->value);
});

test('get_enum_default returns null when there is no matching case', function () {
    expect(get_enum_default(Color::class, null))->toBeNull();
    expect(get_enum_default(Color::class, ''))->toBeNull();
    expect(get_enum_default(Color::class, 'beige'))->toBeNull();
    expect(get_enum_default(UserRole::class, '99'))->toBeNull();
});

// color_name_to_hex

test('color_name_to_hex maps known Tabler colour names to hex values', function (string $name, string $hex) {
    expect(color_name_to_hex($name))->toBe($hex);
})->with([
    ['blue', '#066fd1'],
    ['azure', '#4299e1'],
    ['indigo', '#4263eb'],
    ['purple', '#ae3ec9'],
    ['pink', '#d6336c'],
    ['red', '#d63939'],
    ['orange', '#f76707'],
    ['yellow', '#f59f00'],
    ['lime', '#74b816'],
    ['green', '#2fb344'],
    ['teal', '#0ca678'],
    ['cyan', '#17a2b8'],
]);

test('color_name_to_hex is case-insensitive', function () {
    expect(color_name_to_hex('Blue'))->toBe('#066fd1');
    expect(color_name_to_hex('RED'))->toBe('#d63939');
});
