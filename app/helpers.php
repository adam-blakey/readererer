<?php

use App\Enums\AttendanceStatus;
use App\Enums\Color;
use App\Enums\RegisterStatus;
use App\Models\Ensemble;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;

function logo_url(): string
{
    $logo = config('app.readererer_logo');

    return filled($logo) ? asset($logo) : Vite::asset('resources/images/readererer-long-logo.svg');
}

function apply_terminology(string $line, array $terminology): string
{
    $terminology = array_filter($terminology, 'filled');

    if ($terminology === []) {
        return $line;
    }

    $words = implode('|', array_map(fn ($word) => preg_quote($word, '/'), array_keys($terminology)));

    // The lookbehind skips :placeholders that share a word's name.
    return preg_replace_callback("/(?<![:\\w])($words)(?!\\w)/i", function ($match) use ($terminology) {
        $replacement = $terminology[Str::lower($match[0])];

        return match (true) {
            $match[0] === Str::upper($match[0]) => Str::upper($replacement),
            ctype_upper($match[0][0]) => Str::ucfirst($replacement),
            default => $replacement,
        };
    }, $line);
}

function get_route_name_from_model($model, $route = 'show')
{
    $class_name = get_class_name_from_model($model);
    $route_name = $class_name.'s.'.$route;

    return $route_name;
}

function get_class_name_from_model($model)
{
    $class_path = get_class($model);
    $class_split = explode('\\', $class_path);
    $class_name = strtolower(end($class_split));

    return $class_name;
}

/** Turn a column name into a translated label ("setup_group" -> "Setup group"). */
function clean_attribute_name($dirty_attribute)
{
    $clean_attribute = str_replace('_', ' ', $dirty_attribute);
    $clean_attribute = ucfirst($clean_attribute);

    return __($clean_attribute);
}

/** Table-header label for an attribute: the model's public `$column_labels` entry, else clean_attribute_name(). */
function column_label($model, $attribute)
{
    if (property_exists($model, 'column_labels') && array_key_exists($attribute, $model->column_labels)) {
        return __($model->column_labels[$attribute]);
    }

    return clean_attribute_name($attribute);
}

function member_status_totals($members, $term_date, ?Ensemble $ensemble = null): array
{
    $assume_attending = config('app.readererer_assume_attending');

    $number_attending = 0;
    $number_not_attending = 0;
    $number_unknown = 0;

    foreach ($members as $member) {
        $attendance_value = $member->latestAttendanceFor($term_date, $ensemble)?->status ?? AttendanceStatus::Unknown;

        switch ($attendance_value) {
            case AttendanceStatus::Attending:
                $number_attending++;
                break;
            case AttendanceStatus::NotAttending:
                $number_not_attending++;
                break;
            case AttendanceStatus::Unknown:
                $number_unknown++;
                break;
        }
    }

    if ($assume_attending) {
        return [
            'attending' => $number_attending + $number_unknown,
            'not_attending' => $number_not_attending];
    } else {
        return [
            'attending' => $number_attending,
            'not_attending' => $number_not_attending,
            'unknown' => $number_unknown,
        ];
    }
}

/** Count register entries by status key; members of `$expected_members` with no entry count as unmarked. */
function register_status_totals($entries, int $expected_members): array
{
    $totals = [];
    foreach (RegisterStatus::cases() as $status) {
        $totals[$status->key()] = 0;
    }

    $counted = 0;

    foreach ($entries as $entry) {
        $status = $entry->status ?? RegisterStatus::Unmarked;
        $totals[$status->key()]++;
        $counted++;
    }

    $totals['unmarked'] += max(0, $expected_members - $counted);

    return $totals;
}

function get_validation_rules_for_model(Model $model): array
{
    $request_class = 'App\\Http\\Requests\\'.($model->exists ? 'Update' : 'Store').class_basename($model).'Request';

    if (! class_exists($request_class)) {
        return [];
    }

    // Rules that need request data missing here (no user or route) count as none.
    return rescue(fn () => $request_class::createFrom(request())->rules(), [], false);
}

function rules_require_attribute(array $rules, string $attribute): ?bool
{
    if (! array_key_exists($attribute, $rules)) {
        return null;
    }

    $attribute_rules = is_string($rules[$attribute]) ? explode('|', $rules[$attribute]) : Arr::wrap($rules[$attribute]);

    return in_array('required', $attribute_rules, true);
}

function get_create_fields(object $dummy): array
{
    $columns = collect(Schema::getColumns($dummy->getTable()));
    $fillable = $dummy->getFillable();
    // getCasts() rather than casts(), so models declaring a `$casts` property are covered too.
    $casts = $dummy->getCasts();

    $rules = get_validation_rules_for_model($dummy);

    $fields = [];

    foreach ($fillable as $fillable_entry) {
        if (method_exists($dummy, $fillable_entry) && (($dummy->$fillable_entry() instanceof BelongsToMany) || ($dummy->$fillable_entry() instanceof BelongsTo))) {
            $belongsToRelation = $dummy->$fillable_entry();
            $relatedClass = $belongsToRelation->getRelated();
            $isBelongsToMany = ($dummy->$fillable_entry() instanceof BelongsToMany);

            $name = $fillable_entry;
            $type = 'class';
            $nullable = $isBelongsToMany || belongs_to_is_optional($belongsToRelation, $columns);
            $select_multiple = $isBelongsToMany;
            $default_option = null;
            $icon = call_or_default($dummy, 'getIconForAttribute', $name, 'pencil');
            $options = $relatedClass::orderBy('name')
                ->get();
        } else {
            $column = $columns->firstWhere('name', $fillable_entry) ?? null;
            if (! $column) {
                continue;
            }

            $name = $column['name'];
            $type_name = $column['type_name'];
            $type = map_database_type_to_html($name, $type_name, $casts);

            if ($type === 'enum' || $type === 'color') {
                $enum_class = get_enum_class_for_attribute($casts, $name);
                $options = get_enum_options($enum_class);
                $default_option = get_enum_default($enum_class, $column['default'] ?? null);
            } else {
                $options = [];
                $default_option = $column['default'] ?? null;
            }

            $nullable = $column['nullable'];
            $select_multiple = false;
            $icon = call_or_default($dummy, 'getIconForAttribute', $name, 'pencil');
        }

        $fields[$name] = [
            'label' => clean_attribute_name($name),
            'type' => $type,
            'required' => rules_require_attribute($rules, $name) ?? ! $nullable,
            'icon' => $icon,
            'value' => $dummy->$name,
            'options' => $options,
            'default_option' => $default_option,
            'select_multiple' => $select_multiple,
            'width' => 12,
        ];

    }

    return $fields;
}

function belongs_to_is_optional(BelongsTo $relation, Collection $columns): bool
{
    $column = $columns->firstWhere('name', $relation->getForeignKeyName());

    return (bool) ($column['nullable'] ?? false);
}

function call_or_default(object $object, string $method, mixed $argument, mixed $defaultValue = null): mixed
{
    if (method_exists($object, $method) && is_callable([$object, $method])) {
        return $object->$method($argument) ?? $defaultValue;
    }

    return $defaultValue;
}

/** The enum class an attribute is cast to, or null. */
function get_enum_class_for_attribute(array $casts, string $attribute): ?string
{
    $cast = $casts[$attribute] ?? null;

    return (is_string($cast) && enum_exists($cast)) ? $cast : null;
}

/** Options for an enum field, keyed by the value the form posts (backing value, or case name for a pure enum). */
function get_enum_options(string $enum_class): array
{
    $options = [];

    foreach ($enum_class::cases() as $case) {
        $options[enum_case_value($case)] = enum_case_label($case);
    }

    return $options;
}

function enum_case_value(UnitEnum $case): string|int
{
    return ($case instanceof BackedEnum) ? $case->value : $case->name;
}

/** The enum's own label() for a case, else its name in words ("NotAttending" -> "Not attending"). */
function enum_case_label(UnitEnum $case): string
{
    if (method_exists($case, 'label')) {
        return $case->label();
    }

    return clean_attribute_name(Str::snake($case->name));
}

/** The enum option value matching a column's database default, or null. */
function get_enum_default(string $enum_class, mixed $default): string|int|null
{
    if ($default === null || $default === '') {
        return null;
    }

    // Database defaults come back as strings, and sqlite quotes the string ones.
    $default = trim((string) $default, "'\"");

    foreach ($enum_class::cases() as $case) {
        if ((string) enum_case_value($case) === $default) {
            return enum_case_value($case);
        }
    }

    return null;
}

function map_database_type_to_html(string $name, string $db_type, array $casts): string
{
    $enum_class = get_enum_class_for_attribute($casts, $name);

    if ($enum_class !== null) {
        return ($enum_class === Color::class) ? 'color' : 'enum';
    }

    if ($name == 'image') {
        return 'image';
    } elseif ($name == 'email') {
        return 'email';
    } elseif ($name == 'password') {
        return 'password';
    }

    $html_type = match (strtolower($db_type)) {
        'text', 'longtext', 'mediumtext' => 'textarea',
        'integer', 'bigint', 'smallint', 'decimal', 'float' => 'number',
        'boolean', 'tinyint' => 'boolean',
        'date' => 'date',
        'datetime', 'timestamp' => 'datetime',
        default => 'text'
    };

    return $html_type;
}

function color_name_to_hex(mixed $name): ?string
{
    if ($name instanceof Color) {
        return $name->hex();
    }

    $enum = Color::tryFrom(strtolower((string) $name));

    return $enum ? $enum->hex() : null;
}

function color_name_to_css_class(mixed $name): ?string
{
    if ($name instanceof Color) {
        return $name->cssClass();
    }

    $enum = Color::tryFrom(strtolower((string) $name));

    return $enum ? $enum->cssClass() : null;
}

function unhandled_error_messages(?ViewErrorBag $errors, array $rendered): array
{
    return collect($errors?->getBag('default')->getMessages() ?? [])
        ->reject(fn ($messages, $key) => Str::is($rendered, $key))
        ->flatten()
        ->all();
}
