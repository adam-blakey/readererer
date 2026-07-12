@php
    $attendanceTotals = member_status_totals($members, $termDate);

    function get_seat_row($member, $ensemble) {
        return $member->ensembles->where('id', $ensemble->id)->first()->pivot->seat_row;
    }
    function get_seat_column($member, $ensemble) {
        return $member->ensembles->where('id', $ensemble->id)->first()->pivot->seat_column;
    }
    function get_instrument_color($member, $ensemble, $instrumentFamilies) {
        $family = $instrumentFamilies->firstWhere('id', $member->ensembles->where('id', $ensemble->id)->first()->pivot->instrument_family_id);

        return ($family ? color_name_to_hex($family->color) : null) ?? '#000000';
    }

    $members = $members->sortBy([
        fn ($a, $b) => get_seat_row($a, $ensemble) <=> get_seat_row($b, $ensemble),
        fn ($a, $b) => get_seat_column($a, $ensemble) <=> get_seat_column($b, $ensemble),
    ]);
    $rowGroupedMembers = $members->groupBy(function($member) use ($ensemble) {
        return $member->ensembles->where('id', $ensemble->id)->first()->pivot->seat_row;
    });

    $minRow = $rowGroupedMembers->keys()->min();
    $maxRow = $rowGroupedMembers->keys()->max();
    $numberOfRows = (gettype($minRow) == "string" && gettype($maxRow) == "string")
        ? ord(strtoupper($maxRow)) - ord(strtoupper($minRow))
        : $maxRow - $minRow;
    $numberOfRows++;
@endphp
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Seating plan for {{ $ensemble->name }} on {{ $termDate->name }}</title>
</head>

<body style="font-family: Arial, sans-serif;">

<p style="display: inline-block; padding-right: 20px;"><strong>Exported at: </strong> {{ now() }}</p>
<p style="display: inline-block; padding-right: 20px;"><strong>Date: </strong> {{ $termDate->start_datetime }}</p>
<p style="display: inline-block; padding-right: 20px;"><strong>Ensemble: </strong> {{ $ensemble->name }}</p>
<br />
<p style="display: inline-block; padding-right: 20px;"><strong>Attendees: </strong> {{ $attendanceTotals['attending'] }}</p>
<p style="display: inline-block; padding-right: 20px;"><strong>Absent: </strong> {{ $attendanceTotals['not_attending'] }}</p>
@if (array_key_exists('unknown', $attendanceTotals))
    <p style="display: inline-block; padding-right: 20px;"><strong>Unknown: </strong> {{ $attendanceTotals['unknown'] }}</p>
@endif

<h1 style="margin-top: 0;">Seating plan for {{ $termDate->start_datetime->format('jS M') }}</h1>

<p style="margin-top: 0;">
    <strong>Key: </strong>
    @foreach($instrumentFamilies as $instrumentFamily)
        <span style="display: inline-block; padding: 0 6px; margin-right: 12px; border-left: 6px solid {{ color_name_to_hex($instrumentFamily->color) ?? '#000000' }};">{{ $instrumentFamily->name }}</span>
    @endforeach
    <s style="color: #9CA3AF; text-decoration: line-through; margin-right: 12px;">Not attending</s>
    @if (array_key_exists('unknown', $attendanceTotals))
        <i style="color: #f59f00;">Unknown ?</i>
    @endif
</p>

<section style="display: flex; flex-wrap: wrap; justify-content: space-between;">
    @for($row = $minRow; $row <= $maxRow; $row++)
        @php
            $members = $rowGroupedMembers[$row] ?? [];

            $totals = member_status_totals($members, $termDate);
        @endphp
        <div style="display: inline-block; width: calc({{ 100.0 / $numberOfRows }}% - 30px); vertical-align: top;">
            <table style="border-collapse: collapse; width: 100%;">
                <tr style="text-align: center; background-color: #066fd1; color: #f9fafb;">
                    <th style="padding: 0.15em 0.4em; border: 2px solid black; width: 100%;">Row {{ $row }}</th>
                    <th style="padding: 0.15em 0.4em; border: 2px solid black;">{{ $totals['attending'] }}</th>
                </tr>
                @foreach($members as $member)
                    @php($attendance_value = $member->attendances->where('term_date_id', $termDate->id)->sortByDesc('created_at')->first()?->status ?? App\Enums\AttendanceStatus::Unknown)
                    <tr>
                        <td style="padding: 0.15em 0.4em; border: 2px solid black; border-left: 6px solid {{ get_instrument_color($member, $ensemble, $instrumentFamilies) }};">
                            @switch($attendance_value)
                                @case(App\Enums\AttendanceStatus::Attending)
                                    <span>{{ $member->name }}</span>
                                    @break
                                @case(App\Enums\AttendanceStatus::Unknown)
                                    @if(array_key_exists('unknown', $attendanceTotals))
                                        <i style="color: #f59f00;">{{ $member->name }} ?</i>
                                    @else
                                        <span>{{ $member->name }}</span>
                                    @endif
                                    @break
                                @case(App\Enums\AttendanceStatus::NotAttending)
                                    <s style="color: #9CA3AF; text-decoration: line-through;">{{ $member->name }}</s>
                                    @break
                            @endswitch
                        </td>
                        <td style="padding: 0.15em 0.4em; border: 2px solid black;"></td>
                    </tr>
                @endforeach
        </table>
        </div>
    @endfor
</section>
</body>
</html>
