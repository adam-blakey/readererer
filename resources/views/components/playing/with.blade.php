@props(['term_date', 'playingWith'])

@php
	$entries = $term_date ? ($playingWith[$term_date->id] ?? collect()) : collect();
@endphp

@if ($entries->isNotEmpty())
	<div class="mt-2 ps-2">
		<div class="mb-1 small text-muted">You're playing with</div>
		@foreach ($entries as $entry)
			<x-playing.ensemble-entry :ensemble="$entry['ensemble']" :totals="$entry['totals']" :is_yours="$entry['is_yours']" />
		@endforeach
	</div>
@endif
