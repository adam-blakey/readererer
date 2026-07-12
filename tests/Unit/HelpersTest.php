<?php

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

test('map_database_type_to_html ignores casts keyed by column name and falls back to the database type', function () {
    // The cast check uses in_array() against the cast *values*, so a cast keyed
    // by the column name never matches and the database type wins.
    expect(map_database_type_to_html('status', 'integer', ['status' => 'boolean']))->toBe('number');
});

test('map_database_type_to_html special-cases image and email column names', function () {
    expect(map_database_type_to_html('image', 'varchar', []))->toBe('image');
    expect(map_database_type_to_html('email', 'varchar', []))->toBe('email');
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
    ['datetime', 'date'],
    ['timestamp', 'date'],
    ['varchar', 'text'],
    ['VARCHAR', 'text'],
]);

// color_name_to_hex

test('color_name_to_css_class returns known Tabler class names', function () {
    expect(color_name_to_css_class('blue'))->toBe('blue');
    expect(color_name_to_css_class('Teal'))->toBe('teal');
});

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
