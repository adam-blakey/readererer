<?php

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;

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

function clean_attribute_name($dirty_attribute)
{
    $clean_attribute = str_replace('_', ' ', $dirty_attribute);
    $clean_attribute = ucfirst($clean_attribute);

    return $clean_attribute;
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
        return $model->column_labels[$attribute];
    }

    return clean_attribute_name($attribute);
}

/**
 * The latest recorded attendance status for a member at a term date.
 *
 * Attendance history is append-only, so the most recent record wins; a member
 * with no records is Unknown.
 */
function latest_attendance_status($member, $term_date): AttendanceStatus
{
    $attendance = $member->attendances->where('term_date_id', $term_date->id)->sortByDesc('created_at')->first();

    return $attendance->status ?? AttendanceStatus::Unknown;
}

/**
 * Filter a collection of members down to those attending a term date,
 * counting Unknown members as attending when `readererer_assume_attending`
 * is enabled.
 */
function members_attending($members, $term_date)
{
    $assume_attending = config('app.readererer_assume_attending');

    return $members->filter(function ($member) use ($term_date, $assume_attending) {
        $status = latest_attendance_status($member, $term_date);

        return $status == AttendanceStatus::Attending
            || ($assume_attending && $status == AttendanceStatus::Unknown);
    })->values();
}

function member_status_totals($members, $term_date): array
{
    $assume_attending = config('app.readererer_assume_attending');

    $number_attending = 0;
    $number_not_attending = 0;
    $number_unknown = 0;

    foreach ($members as $member) {
        $attendance_value = latest_attendance_status($member, $term_date);

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

function get_create_fields(object $dummy): array
{
    $columns = collect(Schema::getColumns($dummy->getTable()));
    $fillable = $dummy->getFillable();
    $casts = $dummy->casts();

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
            $enumClass = (property_exists($dummy, 'enums') && array_key_exists($name, $dummy->enums)) ? $dummy->enums[$name] : null;
            if ($enumClass && enum_exists($enumClass)) {
                $type = 'enum';
                $default_option = $column['default'] ?? null;
                $options = collect($enumClass::cases())
                    ->mapWithKeys(fn ($case) => [$case->value => method_exists($case, 'label') ? $case->label() : $case->name])
                    ->all();
            } else {
                $type = map_database_type_to_html($name, $type_name, $casts);
                $default_option = $column['default'] ?? null;
                $options = [];
            }
            $nullable = $column['nullable'];
            $select_multiple = false;
            $icon = call_or_default($dummy, 'getIconForAttribute', $name, 'pencil');
        }

        $fields[$name] = [
            'label' => ucfirst(str_replace('_', ' ', $name)),
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

function map_database_type_to_html(string $name, string $db_type, array $casts): string
{
    if (in_array($name, $casts)) {
        return $casts[$name];
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
