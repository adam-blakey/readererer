@props(['term_dates', 'show_year' => false])

<tr>
	<th>Members</th>
	@foreach ($term_dates as $term_date)
		<th class="text-center poll-date {{ $term_date->is_concert ? 'bg-primary text-bg-primary' : '' }}">
			{{ $term_date->start_datetime->format('M') }}<br />
			<span class="poll-date-date">{{ $term_date->start_datetime->format('j') }}</span><br />
			@if ($show_year)
				{{ $term_date->start_datetime->format('Y') }}<br />
			@endif
			{{ $term_date->start_datetime->format('D') }}<br />
			{{ $term_date->start_datetime->format('G:i') }}<br />
			{{ $term_date->end_datetime->format('G:i') }}<br />
		</th>
	@endforeach
</tr>
