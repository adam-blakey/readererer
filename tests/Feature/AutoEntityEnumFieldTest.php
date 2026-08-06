<?php

use App\Enums\AttendanceStatus;
use App\Enums\Color;
use App\Enums\UserRole;
use App\Models\InstrumentFamily;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

/**
 * Enum columns are rendered by the generic auto-entity form as a select built
 * from the enum's cases; these cover the markup that ends up on the page.
 *
 * Returns one row per option: ['value' => ..., 'label' => ..., 'selected' => ...].
 */
function enum_select_options(string $html, string $name): Collection
{
    $select = preg_split('/<select[^>]*name="'.preg_quote($name, '/').'"/', $html, 2)[1] ?? '';
    $select = preg_split('/<\/select>/', $select, 2)[0];

    preg_match_all('/<option\s+value="([^"]*)"\s*(selected)?[^>]*>\s*(.*?)\s*<\/option>/s', $select, $matches, PREG_SET_ORDER);

    return collect($matches)->map(fn ($match) => [
        'value' => $match[1],
        'label' => $match[3],
        'selected' => $match[2] === 'selected',
    ]);
}

test('an enum column renders as a select of labelled cases', function () {
    $html = $this->actingAs(make_user(UserRole::Admin))->get(route('users.create'))->assertOk()->getContent();

    $options = enum_select_options($html, 'role');

    expect($options->pluck('value')->all())->toBe(['0', '1', '2', '3', '4']);
    expect($options->pluck('label')->all())->toBe(['Guest', 'Ensemble', 'Member', 'Moderator', 'Admin']);
});

test('a create form pre-selects the column\'s database default', function () {
    $html = $this->actingAs(make_user(UserRole::Admin))->get(route('users.create'))->assertOk()->getContent();

    $selected = enum_select_options($html, 'role')->where('selected')->pluck('value');

    expect($selected->all())->toBe([(string) UserRole::Member->value]);
});

test('an edit form pre-selects the record\'s current case', function () {
    $instrumentFamily = InstrumentFamily::create(['name' => 'Bassoons', 'color' => Color::Teal]);

    $options = enum_select_options($this->actingAs(make_user(UserRole::Moderator))
        ->get(route('instrumentfamilys.edit', $instrumentFamily))
        ->assertOk()
        ->getContent(), 'color');

    expect($options)->toHaveCount(count(Color::cases()));
    expect($options->where('selected')->pluck('value')->all())->toBe([Color::Teal->value]);
    expect($options->pluck('label')->all())->toContain(Color::Teal->label());
});

test('a nullable enum select offers an empty option that is selected when there is no value', function () {
    // No model has a nullable enum column yet, so render the field directly.
    View::share('errors', new ViewErrorBag);

    $html = Blade::render('<x-forms.field :name="$name" :data="$data" />', [
        'name' => 'status',
        'data' => [
            'label' => 'Status',
            'type' => 'enum',
            'required' => false,
            'icon' => 'pencil',
            'value' => null,
            'options' => get_enum_options(AttendanceStatus::class),
            'default_option' => null,
            'select_multiple' => false,
            'width' => 12,
        ],
    ]);

    $options = enum_select_options($html, 'status');

    expect($options->pluck('value')->all())->toBe(['', '0', '1', '2']);
    expect($options->pluck('label')->all())->toBe(['—', 'Unknown', 'Attending', 'Not attending']);
    expect($options->where('selected')->pluck('value')->all())->toBe(['']);
});

test('a required enum select offers no empty option', function () {
    $instrumentFamily = InstrumentFamily::create(['name' => 'Bassoons', 'color' => Color::Teal]);

    $options = enum_select_options($this->actingAs(make_user(UserRole::Moderator))
        ->get(route('instrumentfamilys.edit', $instrumentFamily))
        ->assertOk()
        ->getContent(), 'color');

    expect($options->pluck('value')->all())->not->toContain('');
});
