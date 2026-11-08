@props(['ensemble', 'members', 'term_dates'])

<tr class="fw-bold">
	<td>{{ config('app.readererer_assume_attending') ? __('Attending') : __('Attending (no reply)') }}</td>
	@foreach ($term_dates as $term_date)
		@php
			$totals = member_status_totals($members, $term_date, $ensemble);
		@endphp
		<td class="w-1 text-center {{ $term_date->concert_ensembles->contains($ensemble) ? 'bg-primary-subtle' : '' }}">
			{{ $totals['attending'] }}
			@isset($totals['unknown'])
				<span class="text-secondary fw-normal">({{ $totals['unknown'] }})</span>
			@endisset
		</td>
	@endforeach
</tr>
