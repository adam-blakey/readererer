@props(['ensemble', 'term_dates', 'totals'])

<tr class="fw-bold">
	<td>{{ config('app.readererer_assume_attending') ? __('Attending') : __('Attending (no reply)') }}</td>
	@foreach ($term_dates as $term_date)
		<td class="w-1 text-center {{ $term_date->concert_ensembles->contains($ensemble) ? 'bg-primary-subtle' : '' }}">
			{{ $totals[$term_date->id]['attending'] }}
			@isset($totals[$term_date->id]['unknown'])
				<span class="text-secondary fw-normal">({{ $totals[$term_date->id]['unknown'] }})</span>
			@endisset
		</td>
	@endforeach
</tr>
