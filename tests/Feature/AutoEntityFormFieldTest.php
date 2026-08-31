<?php

use App\Enums\Color;
use App\Enums\UserRole;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

/**
 * The generic auto-entity form renders every field through
 * resources/views/components/forms/field.blade.php; these cover the markup it
 * produces for each field type, and how it behaves once a value or a validation
 * error is in play.
 */

/**
 * Render the field component directly, optionally with an error against it.
 */
function render_field(string $name, array $data, ?string $error = null): string
{
    $errors = new ViewErrorBag;

    if ($error !== null) {
        $errors->put('default', new MessageBag([$name => [$error]]));
    }

    View::share('errors', $errors);

    return Blade::render('<x-forms.field :name="$name" :data="$data" />', [
        'name' => $name,
        'data' => array_merge([
            'label' => ucfirst(str_replace('_', ' ', $name)),
            'type' => 'text',
            'required' => false,
            'icon' => 'pencil',
            'value' => null,
            'options' => [],
            'default_option' => null,
            'select_multiple' => false,
            'width' => 12,
        ], $data),
    ]);
}

test('a field error is rendered outside the icon wrapper so the icon stays centred on the control', function () {
    $html = render_field('name', ['type' => 'text'], 'The name field is required.');

    // The message closes the icon wrapper first: inside it, it stretched the
    // wrapper and dragged the vertically-centred icon down with it.
    expect($html)->toMatch('/<\/div>\s*<div class="invalid-feedback d-block">The name field is required\.<\/div>/');

    $icon_wrapper = strstr($html, '<div class="input-icon"');
    $icon_wrapper = strstr($icon_wrapper, '</div>', true);
    expect($icon_wrapper)->not->toContain('invalid-feedback');
});

test('a field without an error renders no feedback element', function () {
    expect(render_field('name', ['type' => 'text']))->not->toContain('invalid-feedback');
});

test('an invalid control is marked up as invalid', function () {
    expect(render_field('name', ['type' => 'text'], 'Nope.'))->toContain('class="form-control is-invalid"');
});

test('a required field label carries the class Tabler asterisks', function () {
    // Tabler only styles .form-label.required, so the old .col-form-label never
    // showed the asterisk.
    expect(render_field('name', ['required' => true]))->toContain('class="form-label required"');
    expect(render_field('name', ['required' => false]))->toContain('class="form-label"');
});

test('a relationship select is a form-select that marks the current values as selected', function () {
    $options = collect([
        (object) ['id' => 1, 'name' => 'Alice'],
        (object) ['id' => 2, 'name' => 'Bob'],
    ]);

    $html = render_field('van_drivers', [
        'type' => 'class',
        'options' => $options,
        'select_multiple' => true,
        'value' => collect([(object) ['id' => 2, 'name' => 'Bob']]),
    ]);

    expect($html)->toContain('name="van_drivers[]"')
        ->toContain('form-select')
        ->toContain('multiple')
        ->toMatch('/<option value="2" ?selected>Bob<\/option>/')
        ->toMatch('/<option value="1" ?>Alice<\/option>/');
});

test('an optional single relationship select offers an empty option', function () {
    $html = render_field('setup_group', [
        'type' => 'class',
        'options' => collect([(object) ['id' => 1, 'name' => 'Group A']]),
        'value' => null,
    ]);

    expect($html)->toMatch('/<option value="" ?selected>—<\/option>/')
        ->toContain('name="setup_group"');
});

test('a boolean column renders a switch with a hidden off value', function () {
    $html = render_field('show', ['type' => 'boolean', 'value' => true]);

    expect($html)->toContain('form-check form-switch')
        ->toContain('<input type="hidden" name="show" value="0" />')
        ->toContain('type="checkbox"')
        ->toContain('checked');

    expect(render_field('show', ['type' => 'boolean', 'value' => false]))->not->toContain('checked');
});

test('date and datetime columns render inputs the browser can edit', function () {
    $date = render_field('start_date', ['type' => 'date', 'value' => '2026-03-04 19:30:00']);
    expect($date)->toContain('type="date"')->toContain('value="2026-03-04"');

    $datetime = render_field('start_datetime', [
        'type' => 'datetime',
        'value' => Carbon::parse('2026-03-04 19:30:00'),
    ]);
    expect($datetime)->toContain('type="datetime-local"')->toContain('value="2026-03-04T19:30"');
});

test('an unparseable date value is handed back to the input rather than throwing', function () {
    expect(render_field('start_date', ['type' => 'date', 'value' => 'not a date']))
        ->toContain('value="not a date"');
});

test('an email column renders an email input and a password column never echoes its value', function () {
    expect(render_field('email', ['type' => 'email', 'value' => 'a@b.com']))
        ->toContain('type="email"')
        ->toContain('value="a@b.com"');

    $password = render_field('password', ['type' => 'password', 'value' => 'hashed-secret']);
    expect($password)->toContain('type="password"')->not->toContain('hashed-secret');
});

test('a colour column is rendered outside the icon wrapper, being a swatch grid rather than one control', function () {
    $html = render_field('color', [
        'type' => 'color',
        'options' => get_enum_options(Color::class),
        'value' => Color::Teal,
    ]);

    // The picker is a radio group, so there is no single control for the icon
    // addon to sit against, and none for the label's `for` to point at.
    expect($html)->not->toContain('input-icon')
        ->not->toContain('for="field-color"')
        ->toContain('color-picker');
});

test('a select keeps the submitted value after a failed validation pass', function () {
    $driver = make_user();
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)
        ->from(route('setupgroups.create'))
        ->post(route('setupgroups.store'), [
            'name' => '',
            'color' => Color::Teal->value,
            'van_drivers' => [$driver->id],
        ])
        ->assertRedirect(route('setupgroups.create'));

    $html = $this->actingAs($admin)->get(route('setupgroups.create'))->assertOk()->getContent();

    expect($html)->toMatch('/<option value="'.$driver->id.'" ?selected>/')
        ->toContain('The name field is required.');
});
