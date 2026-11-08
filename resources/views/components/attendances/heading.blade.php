@props(['term_dates', 'show_year' => false, 'ensemble' => null, 'location_symbols' => collect()])

<tr>
	<th>{{ __('Members') }}</th>
	@foreach ($term_dates as $term_date)
		@php
			$highlight = $ensemble ? $term_date->concert_ensembles->contains($ensemble) : $term_date->is_concert;

			$heading_tooltip = ($term_date->is_concert ? __('Concert') : __('Rehearsal')) . ': ' . $term_date->schedule_label;

			if ($term_date->is_concert) {
			    $heading_tooltip .= ' (' . $term_date->concert_ensemble_names . ')';
			}

			$heading_tooltip .= ' — ' . __('Location: :location', ['location' => $term_date->location_label]);

			if (config('app.readererer_setup_groups') && $term_date->setup_group != null) {
			    $heading_tooltip .= ' — ' . __(':name sets up', ['name' => $term_date->setup_group->label]);
			}
		@endphp
		<th class="text-center poll-date align-text-top {{ $highlight ? 'bg-primary text-bg-primary' : '' }}" data-bs-toggle="tooltip" title="{{ $heading_tooltip }}">
			{{ $term_date->start_datetime->format('M') }}<br />
			<span class="poll-date-date">{{ $term_date->start_datetime->format('j') }}</span><br />
			@if ($show_year)
				{{ $term_date->start_datetime->format('Y') }}<br />
			@endif
			{{ $term_date->start_datetime->format('D') }}<br />
			{{-- Two lines either way, so the setup group badges below stay in line. --}}
			@if ($term_date->time_tbc)
				{{ __('Time') }}<br />
				{{ __('TBC') }}<br />
			@else
				{{ $term_date->start_datetime->format('G:i') }}<br />
				{{ $term_date->end_datetime->format('G:i') }}<br />
			@endif
            @if (config('app.readererer_setup_groups') && $term_date->setup_group != null)
                <div class="mt-2">
                    <x-setup-group-badge :setup_group="$term_date->setup_group" :tooltip="false" />
                </div>
            @endif
			@if ($symbol = $location_symbols[$term_date->inferred_location?->id] ?? null)
				<div class="mt-1"><strong>{{ $symbol }}</strong></div>
			@endif
		</th>
	@endforeach
</tr>
