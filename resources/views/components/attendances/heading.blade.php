@props(['term_dates', 'show_year' => false, 'ensemble' => null])

<tr>
	<th>Members</th>
	@foreach ($term_dates as $term_date)
		@php
			$highlight = $ensemble ? ((int)($term_date->concert_ensemble_id) === (int)($ensemble->id)) : ($term_date->concert_ensemble_id !== null);
		@endphp
		<th class="text-center poll-date {{ $highlight ? 'bg-primary text-bg-primary' : '' }}">
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
