@props(['totals'])

<div class="btn-list">
	<span class="badge bg-green text-green-fg">{{ $totals['attending'] }} playing</span>
	<span class="badge bg-red text-red-fg">{{ $totals['not_attending'] }} not playing</span>
	@isset($totals['unknown'])
		<span class="badge bg-gray text-muted">{{ $totals['unknown'] }} not answered</span>
	@endisset
</div>
