<?php

use App\Enums\Color;
use App\Enums\UserRole;
use App\Models\InstrumentFamily;
use App\Models\SetupGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

/**
 * A column cast to the Color palette is rendered by the generic auto-entity
 * form as a swatch picker: one radio per case, no JavaScript involved. These
 * cover the markup that ends up on the page.
 *
 * Returns one row per swatch: ['value' => ..., 'label' => ..., 'checked' => ...].
 */
function color_picker_radios(string $html, string $name): Collection
{
    preg_match_all(
        '/<input\s+type="radio"\s+name="'.preg_quote($name, '/').'"\s+value="([^"]*)"[^>]*aria-label="([^"]*)"([^>]*)>/s',
        $html,
        $matches,
        PREG_SET_ORDER
    );

    return collect($matches)->map(fn ($match) => [
        'value' => $match[1],
        'label' => $match[2],
        'checked' => str_contains($match[3], 'checked'),
    ]);
}

test('a colour column renders a radio per palette case', function () {
    $html = $this->actingAs(make_user(UserRole::Admin))->get(route('setupgroups.create'))->assertOk()->getContent();

    $radios = color_picker_radios($html, 'color');

    expect($radios->pluck('value')->all())->toBe(collect(Color::cases())->pluck('value')->all());
    expect($radios->pluck('label')->all())->toBe(collect(Color::cases())->map->label()->all());
});

test('each swatch carries its palette colour', function () {
    $html = $this->actingAs(make_user(UserRole::Admin))->get(route('setupgroups.create'))->assertOk()->getContent();

    $normalised = preg_replace('/\s+/', ' ', $html);

    foreach (Color::cases() as $case) {
        expect($normalised)->toContain('class="form-colorinput-color bg-'.$case->value.'" style="background-color: '.$case->hex().'"');
    }
});

test('a create form checks the column\'s database default', function () {
    // instrument_families.color defaults to 'blue'.
    $html = $this->actingAs(make_user(UserRole::Moderator))->get(route('instrumentfamilys.create'))->assertOk()->getContent();

    expect(color_picker_radios($html, 'color')->where('checked')->pluck('value')->all())->toBe([Color::Blue->value]);
});

test('an edit form checks the record\'s current colour', function () {
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => Color::Teal]);

    $html = $this->actingAs(make_user(UserRole::Admin))
        ->get(route('setupgroups.edit', $setupGroup))
        ->assertOk()
        ->getContent();

    expect(color_picker_radios($html, 'color')->where('checked')->pluck('value')->all())->toBe([Color::Teal->value]);
});

test('a required colour picker offers no empty swatch', function () {
    $html = $this->actingAs(make_user(UserRole::Admin))->get(route('setupgroups.create'))->assertOk()->getContent();

    expect(color_picker_radios($html, 'color')->pluck('value')->all())->not->toContain('');
});

test('a nullable colour picker offers an empty swatch that is checked when there is no value', function () {
    // No model has a nullable colour column yet, so render the field directly.
    View::share('errors', new ViewErrorBag);

    $html = Blade::render('<x-forms.field :name="$name" :data="$data" />', [
        'name' => 'color',
        'data' => [
            'label' => 'Colour',
            'type' => 'color',
            'required' => false,
            'icon' => 'paint',
            'value' => null,
            'options' => get_enum_options(Color::class),
            'default_option' => null,
            'select_multiple' => false,
            'width' => 12,
        ],
    ]);

    $radios = color_picker_radios($html, 'color');

    expect($radios->first()['value'])->toBe('');
    expect($radios->where('checked')->pluck('value')->all())->toBe(['']);
});

test('the colour picker keeps the submitted colour after a validation failure', function () {
    $this->actingAs(make_user(UserRole::Admin));

    // Missing name, so the form comes back with the old input.
    $this->post(route('setupgroups.store'), ['color' => Color::Pink->value])
        ->assertSessionHasErrors('name');

    $html = $this->get(route('setupgroups.create'))
        ->assertOk()
        ->getContent();

    expect(color_picker_radios($html, 'color')->where('checked')->pluck('value')->all())->toBe([Color::Pink->value]);
});

test('the colour picker needs no javascript', function () {
    $html = $this->actingAs(make_user(UserRole::Admin))->get(route('setupgroups.create'))->assertOk()->getContent();

    expect($html)->not->toContain('TomSelect');
});

test('a colour column with no palette cast is untouched', function () {
    // Sanity check that the picker is driven by the cast, not the column name.
    expect(get_create_fields(new InstrumentFamily)['name']['type'])->toBe('text');
});
