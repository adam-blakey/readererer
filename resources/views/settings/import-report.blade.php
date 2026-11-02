<table class="table table-vcenter mb-3">
	<tbody>
		@foreach ($report['counts'] as $label => $count)
			<tr>
				<th>{{ __($label) }}</th>
				<td class="text-end">{{ $count }}</td>
			</tr>
		@endforeach
	</tbody>
</table>
@if ($report['warnings'])
	<div class="alert alert-warning" role="alert">
		<h4 class="alert-title">{{ trans_choice(':count warning|:count warnings', count($report['warnings'])) }}</h4>
		<ul class="mb-0">
			@foreach ($report['warnings'] as $warning)
				<li>{{ $warning }}</li>
			@endforeach
		</ul>
	</div>
@endif
