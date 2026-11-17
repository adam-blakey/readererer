@include('settings.import-report', ['report' => $report])
@if ($report['leavers'])
	<h3>{{ __('No longer active in Membermojo') }}</h3>
	<ul>
		@foreach ($report['leavers'] as $name)
			<li>{{ $name }}</li>
		@endforeach
	</ul>
@endif
