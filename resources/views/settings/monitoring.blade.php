@props(['period', 'health', 'exceptions', 'slow_requests', 'slow_queries', 'slow_outgoing_requests', 'active_users'])

@php
	use Laravel\Pulse\Recorders;

	$periods = [
	    '1_hour' => __('1 hour'),
	    '6_hours' => __('6 hours'),
	    '24_hours' => __('24 hours'),
	    '7_days' => __('7 days'),
	];
	$threshold = fn (string $recorder) => number_format((int) config('pulse.recorders.'.$recorder.'.threshold'));
	$latest = $health['latest'];
	$stale = $latest && $latest->at->lt(now()->subMinutes(15));
@endphp

<x-layout>
	<div class="container-xl">
		<div class="mb-3 d-flex justify-content-end">
			<div aria-label="{{ __('Period') }}" class="btn-group" role="group">
				@foreach ($periods as $key => $label)
					<a @class(['btn', 'active' => $period === $key]) @if ($period === $key) aria-current="page" @endif href="{{ route('settings.monitoring', ['period' => $key]) }}">{{ $label }}</a>
				@endforeach
			</div>
		</div>

		<x-card-row>
			<div class="col-12">
				<x-card>
					<x-card-header>{{ __('Health check') }}</x-card-header>
					<x-card-body>
						@if (! $latest)
							<div class="text-muted">{{ __('The health check has not run yet. It runs every five minutes from the scheduler.') }}</div>
						@else
							@if ($stale)
								<div class="alert alert-warning" role="alert">{{ __('The last health check was :time. Check that the scheduler\'s cron job is running.', ['time' => $latest->at->diffForHumans()]) }}</div>
							@endif
							<div class="datagrid">
								<div class="datagrid-item">
									<div class="datagrid-title">{{ __('Status') }}</div>
									<div class="datagrid-content">
										@if ($latest->status === 'up')
											<span class="badge bg-green text-green-fg">{{ __('Up') }}</span>
										@else
											<span class="badge bg-red text-red-fg" title="{{ $latest->error }}">{{ __('Down') }}</span>
										@endif
									</div>
								</div>
								<div class="datagrid-item">
									<div class="datagrid-title">{{ __('Last checked') }}</div>
									<div class="datagrid-content" title="{{ $latest->at }}">{{ $latest->at->diffForHumans() }}</div>
								</div>
								<div class="datagrid-item">
									<div class="datagrid-title">{{ __('Uptime') }}</div>
									<div class="datagrid-content">{{ $health['uptime'] === null ? '—' : $health['uptime'].'%' }}</div>
								</div>
								<div class="datagrid-item">
									<div class="datagrid-title">{{ __('Average response') }}</div>
									<div class="datagrid-content">{{ $health['average'] === null ? '—' : __(':ms ms', ['ms' => number_format($health['average'])]) }}</div>
								</div>
								<div class="datagrid-item">
									<div class="datagrid-title">{{ __('Checks') }}</div>
									<div class="datagrid-content">{{ number_format($health['checks']) }}</div>
								</div>
							</div>
							@if ($latest->error)
								<div class="mt-3 text-danger">{{ $latest->error }}</div>
							@endif
						@endif
					</x-card-body>
				</x-card>
			</div>

			<div class="col-12">
				<x-card>
					<x-card-header>{{ __('Exceptions') }}</x-card-header>
					@if ($exceptions->isEmpty())
						<x-card-body><span class="text-muted">{{ __('No exceptions in this period.') }}</span></x-card-body>
					@else
						<div class="table-responsive">
							<table class="table table-vcenter card-table">
								<thead>
									<tr>
										<th>{{ __('Exception') }}</th>
										<th class="text-end">{{ __('Count') }}</th>
										<th class="text-end">{{ __('Latest') }}</th>
									</tr>
								</thead>
								<tbody>
									@foreach ($exceptions as $exception)
										@php($at = Carbon\CarbonImmutable::createFromTimestamp($exception->max))
										<tr>
											<td class="text-break">
												<div class="font-monospace">{{ $exception->class }}</div>
												<div class="small text-muted">{{ $exception->location }}</div>
											</td>
											<td class="text-end">{{ number_format($exception->count) }}</td>
											<td class="text-end text-nowrap" title="{{ $at }}">{{ $at->diffForHumans() }}</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					@endif
				</x-card>
			</div>

			<div class="col-12">
				<x-card>
					<x-card-header>{{ __('Slow requests') }}</x-card-header>
					<x-card-body><span class="text-muted">{{ __('Pages that took longer than :ms ms.', ['ms' => $threshold(Recorders\SlowRequests::class)]) }}</span></x-card-body>
					@unless ($slow_requests->isEmpty())
						<div class="table-responsive">
							<table class="table table-vcenter card-table">
								<thead>
									<tr>
										<th>{{ __('Request') }}</th>
										<th class="text-end">{{ __('Count') }}</th>
										<th class="text-end">{{ __('Slowest') }}</th>
									</tr>
								</thead>
								<tbody>
									@foreach ($slow_requests as $slow_request)
										<tr>
											<td class="text-break">
												<div class="font-monospace"><span class="badge me-1">{{ $slow_request->method }}</span>{{ $slow_request->uri }}</div>
												<div class="small text-muted">{{ $slow_request->action }}</div>
											</td>
											<td class="text-end">{{ number_format($slow_request->count) }}</td>
											<td class="text-end text-nowrap">{{ __(':ms ms', ['ms' => number_format($slow_request->max)]) }}</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					@endunless
				</x-card>
			</div>

			<div class="col-12">
				<x-card>
					<x-card-header>{{ __('Slow queries') }}</x-card-header>
					<x-card-body><span class="text-muted">{{ __('Database queries that took longer than :ms ms.', ['ms' => $threshold(Recorders\SlowQueries::class)]) }}</span></x-card-body>
					@unless ($slow_queries->isEmpty())
						<div class="table-responsive">
							<table class="table table-vcenter card-table">
								<thead>
									<tr>
										<th>{{ __('Query') }}</th>
										<th class="text-end">{{ __('Count') }}</th>
										<th class="text-end">{{ __('Slowest') }}</th>
									</tr>
								</thead>
								<tbody>
									@foreach ($slow_queries as $slow_query)
										<tr>
											<td class="text-break">
												<code>{{ $slow_query->sql }}</code>
												<div class="small text-muted">{{ $slow_query->location }}</div>
											</td>
											<td class="text-end">{{ number_format($slow_query->count) }}</td>
											<td class="text-end text-nowrap">{{ __(':ms ms', ['ms' => number_format($slow_query->max)]) }}</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					@endunless
				</x-card>
			</div>

			<div class="col-md-6">
				<x-card>
					<x-card-header>{{ __('Slow outgoing requests') }}</x-card-header>
					<x-card-body><span class="text-muted">{{ __('Requests to other services that took longer than :ms ms.', ['ms' => $threshold(Recorders\SlowOutgoingRequests::class)]) }}</span></x-card-body>
					@unless ($slow_outgoing_requests->isEmpty())
						<div class="table-responsive">
							<table class="table table-vcenter card-table">
								<thead>
									<tr>
										<th>{{ __('Request') }}</th>
										<th class="text-end">{{ __('Count') }}</th>
										<th class="text-end">{{ __('Slowest') }}</th>
									</tr>
								</thead>
								<tbody>
									@foreach ($slow_outgoing_requests as $outgoing)
										<tr>
											<td class="text-break font-monospace"><span class="badge me-1">{{ $outgoing->method }}</span>{{ $outgoing->uri }}</td>
											<td class="text-end">{{ number_format($outgoing->count) }}</td>
											<td class="text-end text-nowrap">{{ __(':ms ms', ['ms' => number_format($outgoing->max)]) }}</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					@endunless
				</x-card>
			</div>

			<div class="col-md-6">
				<x-card>
					<x-card-header>{{ __('Most active users') }}</x-card-header>
					@if ($active_users->isEmpty())
						<x-card-body><span class="text-muted">{{ __('Nobody has used the site in this period.') }}</span></x-card-body>
					@else
						<div class="table-responsive">
							<table class="table table-vcenter card-table">
								<thead>
									<tr>
										<th>{{ __('User') }}</th>
										<th class="text-end">{{ __('Requests') }}</th>
									</tr>
								</thead>
								<tbody>
									@foreach ($active_users as $row)
										<tr>
											<td><x-user-entry :user="$row->user" /></td>
											<td class="text-end">{{ number_format($row->count) }}</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					@endif
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>
