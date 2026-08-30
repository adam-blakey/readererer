@props(['term_dates', 'show_year' => false, 'ensemble' => null])

<tr>
	<th>Members</th>
	@foreach ($term_dates as $term_date)
		@php
			$highlight = $ensemble ? ((int)($term_date->concert_ensemble_id) === (int)($ensemble->id)) : ($term_date->concert_ensemble_id !== null);

			// The column is stacked abbreviations — "Aug / 6 / Wed / 19:30 / 21:30"
			// — so spell the date out in full, and say whether it is a concert,
			// on hover.
			$heading_tooltip = ($term_date->concert_ensemble_id ? 'Concert' : 'Rehearsal') . ': ' . $term_date->schedule_label;

			if ($term_date->concert_ensemble) {
			    $heading_tooltip .= ' (' . $term_date->concert_ensemble->name . ')';
			}

			if ($term_date->setup_group != null) {
			    $heading_tooltip .= ' — ' . $term_date->setup_group->label . ' sets up';
			}
		@endphp
		<th class="text-center poll-date align-text-top {{ $highlight ? 'bg-primary text-bg-primary' : '' }}" data-bs-toggle="tooltip" title="{{ $heading_tooltip }}">
			{{ $term_date->start_datetime->format('M') }}<br />
			<span class="poll-date-date">{{ $term_date->start_datetime->format('j') }}</span><br />
			@if ($show_year)
				{{ $term_date->start_datetime->format('Y') }}<br />
			@endif
			{{ $term_date->start_datetime->format('D') }}<br />
			{{ $term_date->start_datetime->format('G:i') }}<br />
			{{ $term_date->end_datetime->format('G:i') }}<br />
            @if ($term_date->setup_group != null)
                <div class="mt-2">
                    <x-setup-group-badge :setup_group="$term_date->setup_group" :tooltip="false" />
                </div>
            @endif
		</th>
	@endforeach
</tr>
