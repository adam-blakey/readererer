@php
    use App\Enums\AttendanceStatus;
    use App\Features\SeatingPlanPdfOptions;
    use Laravel\Pennant\Feature;

    $options = Feature::active(SeatingPlanPdfOptions::class);
    $abbreviate = $options && config('app.readererer_abbreviate_surnames');
    $attendanceTotals = $termDate ? member_status_totals($members, $termDate, $ensemble) : [];
    $primaryColor = color_name_to_hex(config('app.readererer_primary_color')) ?? '#066fd1';

    $members = $members->sortBy([
        ['pivot.seat_row', 'asc'],
        ['pivot.seat_column', 'asc'],
    ]);
    $rowGroupedMembers = $members->groupBy('pivot.seat_row');

    $rows = collect(seat_rows($rowGroupedMembers->keys()))->skipUntil(fn ($row) => $rowGroupedMembers->has($row));

    $key = $instrumentFamilies->map(fn ($instrumentFamily) => [
        'color' => color_name_to_hex($instrumentFamily->color) ?? '#000000',
        'html' => e($instrumentFamily->name),
    ]);
    if ($termDate) {
        $key->push(['color' => '#ffffff', 'html' => '<s style="color: #9CA3AF;">'.e(__('Not attending')).'</s>']);
        if (array_key_exists('unknown', $attendanceTotals)) {
            $key->push(['color' => '#ffffff', 'html' => '<i style="color: #f59f00;">'.e(__('Unknown ?')).'</i>']);
        }
    }
    $keyColumns = 6;
@endphp
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{{ $termDate ? __('Seating plan for :ensemble on :date', ['ensemble' => $ensemble->name, 'date' => $termDate->name]) : __('Seating plan for :ensemble', ['ensemble' => $ensemble->name]) }}</title>
</head>

<body style="font-family: Arial, sans-serif; font-size: 12px;">

<p style="display: inline-block; padding-right: 20px;"><strong>{{ __('Exported at') }}: </strong> {{ now() }}</p>
@if ($termDate)
    <p style="display: inline-block; padding-right: 20px;"><strong>{{ __('Date') }}: </strong> {{ $termDate->start_datetime }}</p>
@endif
<p style="display: inline-block; padding-right: 20px;"><strong>{{ __('Ensemble') }}: </strong> {{ $ensemble->name }}</p>
@if ($termDate)
    <br />
    <p style="display: inline-block; padding-right: 20px;"><strong>{{ __('Attendees') }}: </strong> {{ $attendanceTotals['attending'] }}</p>
    <p style="display: inline-block; padding-right: 20px;"><strong>{{ __('Absent') }}: </strong> {{ $attendanceTotals['not_attending'] }}</p>
    @if (array_key_exists('unknown', $attendanceTotals))
        <p style="display: inline-block; padding-right: 20px;"><strong>{{ __('Unknown') }}: </strong> {{ $attendanceTotals['unknown'] }}</p>
    @endif
@endif

<h1 style="margin-top: 0;">{{ $termDate ? __('Seating plan for :date', ['date' => $termDate->start_datetime->format('jS M')]) : __('Seating plan') }}</h1>

<table style="width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 0 4px; margin-bottom: 8px;">
    @foreach ($key->chunk($keyColumns) as $keyRow)
        <tr>
            <th style="width: 6%; text-align: left; vertical-align: top;">{{ $loop->first ? __('Key').':' : '' }}</th>
            @foreach ($keyRow as $entry)
                <td style="width: {{ 94 / $keyColumns }}%; padding: 0 6px; border-left: 6px solid {{ $entry['color'] }}; vertical-align: top;">{!! $entry['html'] !!}</td>
            @endforeach
            @for ($i = $keyRow->count(); $i < $keyColumns; $i++)
                <td></td>
            @endfor
        </tr>
    @endforeach
</table>

<table style="width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px;">
    <tr>
        @foreach($rows as $row)
            @php($rowMembers = $rowGroupedMembers[$row] ?? collect())
            <td style="vertical-align: top; padding: 0;">
                <table style="border-collapse: collapse; width: 100%;">
                    <tr style="text-align: center; background-color: {{ $primaryColor }}; color: #f9fafb;">
                        <th style="padding: 0.15em 0.4em; border: 2px solid black; width: 100%;">{{ __('Row :number', ['number' => $row]) }}</th>
                        <th style="padding: 0.15em 0.2em; border: 2px solid black; width: 22px;">{{ $termDate ? member_status_totals($rowMembers, $termDate, $ensemble)['attending'] : $rowMembers->count() }}</th>
                    </tr>
                    @foreach($rowMembers as $member)
                        @php($name = $abbreviate ? $member->abbreviated_name : $member->name)
                        @php($attendance = $termDate ? $member->latestAttendanceFor($termDate, $ensemble) : null)
                        @php($status = $termDate ? ($attendance?->status ?? AttendanceStatus::Unknown)->effective() : AttendanceStatus::Attending)
                        <tr>
                            <td style="padding: 0.15em 0.4em; border: 2px solid black; border-left: 6px solid {{ color_name_to_hex($instrumentFamilies->get($member->pivot->instrument_family_id)?->color) ?? '#000000' }}; word-wrap: break-word;">
                                @switch($status)
                                    @case(AttendanceStatus::Unknown)
                                        <i style="color: #f59f00;">{{ $name }} ?</i>
                                        @break
                                    @case(AttendanceStatus::NotAttending)
                                        <s style="color: #9CA3AF; text-decoration: line-through;">{{ $name }}</s>
                                        @break
                                    @case(AttendanceStatus::Depping)
                                        @if ($options)
                                            <div>{{ $attendance->dep_name ?: $name }}</div>
                                            <div style="font-size: 9px; line-height: 1.2; color: #6b7280;">{{ $attendance->dep_name ? __('Dep for :name', ['name' => $name]) : __('Sending a dep') }}</div>
                                        @else
                                            <span>{{ $attendance->dep_name ? __(':dep (dep for :name)', ['dep' => $attendance->dep_name, 'name' => $name]) : __(':name (sending a dep)', ['name' => $name]) }}</span>
                                        @endif
                                        @break
                                    @default
                                        <span>{{ $name }}</span>
                                @endswitch
                            </td>
                            <td style="padding: 0.15em 0.2em; border: 2px solid black;"></td>
                        </tr>
                    @endforeach
                </table>
            </td>
        @endforeach
    </tr>
</table>
</body>
</html>
