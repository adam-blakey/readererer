<?php

use App\Enums\AttendanceStatus;
use App\Enums\RegisterStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

/**
 * Turn a column name into a human label ("setup_group" -> "Setup group").
 *
 * The label is passed through the translator, so a locale can override any of
 * them in its message file; untranslated labels fall through as the English
 * text they already were.
 */
function clean_attribute_name($dirty_attribute)
{
    $clean_attribute = str_replace('_', ' ', $dirty_attribute);
    $clean_attribute = ucfirst($clean_attribute);

    return __($clean_attribute);
}

/**
 * Resolve the label to show for an attribute in a table header.
 *
 * A model may declare a public `$column_labels` map (attribute => label) to
 * override the generated name with something friendlier; anything not listed
 * there falls back to clean_attribute_name().
 */
function column_label($model, $attribute)
{
    if (property_exists($model, 'column_labels') && array_key_exists($attribute, $model->column_labels)) {
        return __($model->column_labels[$attribute]);
    }

    return clean_attribute_name($attribute);
}

function member_status_totals($members, $term_date): array
{
    $assume_attending = config('app.readererer_assume_attending');

    $number_attending = 0;
    $number_not_attending = 0;
    $number_unknown = 0;

    foreach ($members as $member) {
        $attendance = $member->attendances->where('term_date_id', $term_date->id)->sortByDesc('created_at')->first();
        $attendance_value = $attendance->status ?? AttendanceStatus::Unknown;

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

/**
 * Count each register status across a set of register entries, keyed by the
 * status name ('present', 'late', 'absent', 'unmarked').
 *
 * `$expected_members` is the number of members the register covers, so that
 * members with no entry at all are counted as unmarked.
 */
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

    // Members with no entry at all have not been marked either.
    $totals['unmarked'] += max(0, $expected_members - $counted);

    return $totals;
}

function get_create_fields(object $dummy): array
{
    $columns = collect(Schema::getColumns($dummy->getTable()));
    $fillable = $dummy->getFillable();
    // getCasts() rather than casts(), so models declaring a `$casts` property
    // are covered too — and it is public on every model.
    $casts = $dummy->getCasts();

    $fields = [];

    foreach ($fillable as $fillable_entry) {
        if (method_exists($dummy, $fillable_entry) && (($dummy->$fillable_entry() instanceof BelongsToMany) || ($dummy->$fillable_entry() instanceof BelongsTo))) {
            $belongsToRelation = $dummy->$fillable_entry();
            $relatedClass = $belongsToRelation->getRelated();
            $isBelongsToMany = ($dummy->$fillable_entry() instanceof BelongsToMany);

            $name = $fillable_entry;
            $type = 'class';
            $nullable = $isBelongsToMany;
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

            if ($type === 'enum') {
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
            'required' => ! $nullable,
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

function call_or_default(object $object, string $method, mixed $argument, mixed $defaultValue = null): mixed
{
    if (method_exists($object, $method) && is_callable([$object, $method])) {
        return $object->$method($argument) ?? $defaultValue;
    }

    return $defaultValue;
}

/**
 * Resolve the enum class backing an attribute, or null when it has none.
 *
 * Enum columns are declared the way they are anywhere else in Laravel — as an
 * enum cast on the model — so the generic form picks them up without needing
 * any extra annotation.
 */
function get_enum_class_for_attribute(array $casts, string $attribute): ?string
{
    $cast = $casts[$attribute] ?? null;

    return (is_string($cast) && enum_exists($cast)) ? $cast : null;
}

/**
 * Build the options for an enum field, keyed by the value the form posts
 * (the backing value, or the case name for a pure enum).
 */
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

/**
 * The label to show for an enum case: whatever the enum's own label() returns,
 * otherwise the case name split into words ("NotAttending" -> "Not attending").
 */
function enum_case_label(UnitEnum $case): string
{
    if (method_exists($case, 'label')) {
        return $case->label();
    }

    return clean_attribute_name(Str::snake($case->name));
}

/**
 * Coerce a column's database default into the matching enum option value so
 * the generic form can pre-select it. Null when the default matches no case.
 */
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
    if (get_enum_class_for_attribute($casts, $name) !== null) {
        return 'enum';
    }

    if ($name == 'image') {
        return 'image';
    } elseif ($name == 'email') {
        return 'email';
    }

    $html_type = match (strtolower($db_type)) {
        'text', 'longtext', 'mediumtext' => 'textarea',
        'integer', 'bigint', 'smallint', 'decimal', 'float' => 'number',
        'boolean', 'tinyint' => 'boolean',
        'date', 'datetime', 'timestamp' => 'date',
        default => 'text'
    };

    return $html_type;
}

function color_name_to_hex(mixed $name): ?string
{
    if ($name instanceof \App\Enums\Color) {
        return $name->hex();
    }

    $enum = \App\Enums\Color::tryFrom(strtolower((string) $name));
    return $enum ? $enum->hex() : null;
}

function color_name_to_css_class(mixed $name): ?string
{
    if ($name instanceof \App\Enums\Color) {
        return $name->cssClass();
    }

    $enum = \App\Enums\Color::tryFrom(strtolower((string) $name));
    return $enum ? $enum->cssClass() : null;
}
