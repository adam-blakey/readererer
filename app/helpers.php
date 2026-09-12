<?php

use App\Enums\AttendanceStatus;
use App\Enums\RegisterStatus;
use Illuminate\Database\Eloquent\Model;
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

/**
 * Resolve the FormRequest class that governs writes to a model, or null when
 * the model has none.
 *
 * The CRUD controllers name their requests after the model they write
 * (`SetupGroup` -> `StoreSetupGroupRequest` / `UpdateSetupGroupRequest`), so
 * the model's class name is all that is needed to find them.
 */
function get_form_request_class_for_model(object $model, bool $update = false): ?string
{
    $class = 'App\\Http\\Requests\\'.($update ? 'Update' : 'Store').class_basename($model).'Request';

    return class_exists($class) ? $class : null;
}

/**
 * The validation rules that will be applied to a write of this model, keyed by
 * attribute, or null when no FormRequest covers it.
 *
 * The rules are read outside of a request cycle, so a request whose rules()
 * leans on the incoming request or route is no help here — it is treated the
 * same as having no request at all, and the caller falls back to whatever it
 * would have done without one.
 */
function get_validation_rules_for_model(object $model, bool $update = false): ?array
{
    $request_class = get_form_request_class_for_model($model, $update);

    if (! $request_class) {
        return null;
    }

    return rescue(fn () => (new $request_class)->rules(), null, false);
}

/**
 * Whether a set of validation rules makes an attribute mandatory.
 *
 * Returns null when the rules say nothing about the attribute at all, so that
 * a caller can tell "the rules allow this to be empty" from "the rules have no
 * opinion" and fall back accordingly.
 *
 * Only a bare `required` counts: a conditional rule (`required_if`,
 * `required_with`, ...) depends on what else was submitted, which the browser
 * cannot enforce from a static attribute.
 */
function rules_require_attribute(array $rules, string $attribute): ?bool
{
    if (! array_key_exists($attribute, $rules)) {
        return null;
    }

    $attribute_rules = $rules[$attribute];

    if (is_string($attribute_rules)) {
        $attribute_rules = explode('|', $attribute_rules);
    } elseif (! is_array($attribute_rules)) {
        $attribute_rules = [$attribute_rules];
    }

    foreach ($attribute_rules as $rule) {
        if (is_string($rule) && strtolower($rule) === 'required') {
            return true;
        }
    }

    return false;
}

/**
 * Build the field list the generic form renders for a model.
 *
 * Whether a field is mandatory is decided by the FormRequest that will
 * validate the submission, where the model has one, so that the asterisk and
 * the browser's own `required` enforcement agree with the server. Attributes
 * the rules say nothing about fall back to the column's nullability.
 */
function get_create_fields(object $dummy): array
{
    $columns = collect(Schema::getColumns($dummy->getTable()));
    $fillable = $dummy->getFillable();
    // getCasts() rather than casts(), so models declaring a `$casts` property
    // are covered too — and it is public on every model.
    $casts = $dummy->getCasts();

    // An existing record is being edited, a fresh one created; the two are
    // validated by different requests.
    $rules = get_validation_rules_for_model($dummy, $dummy instanceof Model && $dummy->exists) ?? [];

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
    $enum_class = get_enum_class_for_attribute($casts, $name);

    if ($enum_class !== null) {
        // The palette enum gets the colour picker rather than a plain select,
        // whatever the column happens to be called.
        return ($enum_class === \App\Enums\Color::class) ? 'color' : 'enum';
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
