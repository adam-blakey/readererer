<?php

use Illuminate\Support\Facades\Schema;

function get_route_name_from_model($model, $route = 'show')
{
    $class_name = get_class_name_from_model($model);
    $route_name = $class_name . 's.' . $route;

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

function member_status_totals($members, $term_date): array
{
    $assume_attending = config('app.readererer_assume_attending');

    $number_attending = 0;
    $number_not_attending = 0;
    $number_unknown = 0;

    foreach ($members as $member) {
        $attendance = $member->attendances->where('term_date_id', $term_date->id)->sortByDesc('created_at')->first();
        $attendance_value = $attendance->status ?? App\Enums\AttendanceStatus::Unknown;

        switch ($attendance_value) {
            case App\Enums\AttendanceStatus::Attending:
                $number_attending++;
                break;
            case App\Enums\AttendanceStatus::NotAttending:
                $number_not_attending++;
                break;
            case App\Enums\AttendanceStatus::Unknown:
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
            'unknown' => $number_unknown
        ];
    }
}

function get_create_fields(object $dummy): array
{
    $columns = collect(Schema::getColumns($dummy->getTable()));
    $fillable = $dummy->getFillable();

    $fields = [];

    foreach ($fillable as $fillable_entry) {
        $column = $columns->firstWhere('name', $fillable_entry) ?? null;
        if (!$column) {
            continue;
        }

        $name = $column['name'];
        $type_name = $column['type_name'];
        $nullable = $column['nullable'];

        $fields[] = [
            'name' => $name,
            'label' => ucfirst(str_replace('_', ' ', $name)),
            'type' => map_database_type_to_html($type_name),
            'required' => !$nullable,
            'icon' => 'pencil',
            'width' => 12
        ];
    }

    return $fields;
}

function map_database_type_to_html(string $db_type): string {
    return match(strtolower($db_type)) {
        'text', 'longtext', 'mediumtext'                    => 'textarea',
        'integer', 'bigint', 'smallint', 'decimal', 'float' => 'number',
        'boolean', 'tinyint'                                => 'checkbox',
        'date', 'datetime', 'timestamp'                     => 'date',
        default                                             => 'text'
        // TODO: deal with enums nicely
    };
}
