@props(['members', 'term'])

<div class="table-responsive">
    <table class="table table-vcenter card-table text-nowrap datatable">
        <thead>
            <tr>
                <th>Members</th>
                @foreach ($term->term_dates as $term_date)
                    <th class="text-center poll-date-heading">
                        {{ $term_date->start_datetime->format('M') }}<br />
                        <span class="poll-date">{{ $term_date->start_datetime->format('j') }}</span><br />
                        {{ $term_date->start_datetime->format('D') }}<br />
                        {{ $term_date->start_datetime->format('G:i') }}<br />
                        {{ $term_date->end_datetime->format('G:i') }}<br />
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($members as $member)
                <tr>
                    <td>
                        {{ $member->name }}
                    </td>
                    @foreach ($term->term_dates as $term_date)
                        <td class="w-1">
                            @php
                                $attendance = $member->attendances->where('term_date_id', $term_date->id)->first();
                            @endphp
                            @if ($attendance)
                                {{ $attendance->status }}
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
