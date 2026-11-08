@props(['period', 'latest', 'summary', 'availability', 'buckets', 'failures', 'requests', 'exception_count', 'cache', 'exceptions', 'slow_requests', 'slow_queries', 'slow_outgoing_requests', 'active_users'])

@php
	use App\Http\Controllers\MonitoringController;
	use Laravel\Pulse\Recorders;

	$periods = [
	    '1_hour' => __('1 hour'),
	    '6_hours' => __('6 hours'),
	    '24_hours' => __('24 hours'),
	    '7_days' => __('7 days'),
	];
	$threshold = fn (string $recorder) => number_format((int) config('pulse.recorders.'.$recorder.'.threshold'));
	$ms = fn (?int $value) => $value === null ? '—' : __(':ms ms', ['ms' => number_format($value)]);
	$percent = fn (?float $value) => $value === null ? '—' : rtrim(rtrim(number_format($value, 2), '0'), '.').'%';
	$stale = $latest && $latest->at->lt(now()->subMinutes(3 * MonitoringController::CHECK_MINUTES));
	$colour = match (true) {
	    ! $latest => 'secondary',
	    $latest->status === 'down' => 'red',
	    $stale => 'yellow',
	    default => 'green',
	};
	$bucket_colours = ['up' => 'bg-green', 'degraded' => 'bg-yellow', 'down' => 'bg-red', 'none' => 'bg-secondary-lt'];
	$bucket_labels = ['up' => __('Up'), 'degraded' => __('Some checks failed'), 'down' => __('Down'), 'none' => __('No checks')];
	$cache_total = $cache->hits + $cache->misses;
	$phone_start = max(0, $buckets->count() - 30);

	$plotted = $buckets->filter(fn ($bucket) => $bucket->average !== null);
	$slowest = max(1, (int) $plotted->max('average'));
	$points = $plotted->map(fn ($bucket, $i) => round($i / max(1, $buckets->count() - 1) * 100, 2).','.round(38 - $bucket->average / $slowest * 34, 2))->implode(' ');
@endphp

<x-layout>
	<div class="container-xl">
		<div class="row g-3 align-items-center mb-3">
			<div class="col-auto">
				<span class="status-indicator status-{{ $colour }} {{ $colour === 'green' ? 'status-indicator-animated' : '' }}">
					<span class="status-indicator-circle"></span>
					<span class="status-indicator-circle"></span>
					<span class="status-indicator-circle"></span>
				</span>
			</div>
			<div class="col">
				<h2 class="page-title">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</h2>
				<ul class="list-inline list-inline-dots mb-0 text-secondary">
					@if ($latest)
						<li class="list-inline-item"><span class="text-{{ $colour }}">{{ $latest->status === 'up' ? __('Up') : __('Down') }}</span></li>
						<li class="list-inline-item" title="{{ $latest->at }}">{{ __('Last checked :time', ['time' => $latest->at->diffForHumans()]) }}</li>
					@endif
					<li class="list-inline-item">{{ __('Checked every :minutes minutes', ['minutes' => MonitoringController::CHECK_MINUTES]) }}</li>
				</ul>
			</div>
			<div class="col-md-auto ms-auto">
				<div class="btn-list">
					<div aria-label="{{ __('Period') }}" class="btn-group" role="group">
						@foreach ($periods as $key => $label)
							<a @class(['btn', 'active' => $period === $key]) @if ($period === $key) aria-current="page" @endif href="{{ route('settings.monitoring', ['period' => $key]) }}">{{ $label }}</a>
						@endforeach
					</div>
					<a class="btn" href="{{ route('settings.monitoring.logs') }}"><x-icon name="file-text" /> {{ __('Application log') }}</a>
				</div>
			</div>
		</div>

		@if (! $latest)
			<div class="alert alert-info" role="alert">{{ __('The health check has not run yet. It runs every five minutes from the scheduler.') }}</div>
		@elseif ($stale)
			<div class="alert alert-warning" role="alert">{{ __('The last health check was :time. Check that the scheduler\'s cron job is running.', ['time' => $latest->at->diffForHumans()]) }}</div>
		@endif

		<div class="row row-cards">
			<div class="col-sm-6 col-lg-3">
				<x-stat-card icon="activity" :title="__('Uptime')" :value="$percent($summary->uptime)" />
			</div>
			<div class="col-sm-6 col-lg-3">
				<x-stat-card icon="bolt" :title="__('Average response')" :value="$ms($summary->average)" />
			</div>
			<div class="col-sm-6 col-lg-3">
				<x-stat-card icon="users" :title="__('User requests')" :value="number_format($requests)" />
			</div>
			<div class="col-sm-6 col-lg-3">
				<x-stat-card icon="alert-triangle" :title="__('Exceptions')" :value="number_format($exception_count)" />
			</div>

			<div class="col-lg-8">
				<div class="card h-100">
					<div class="card-header">
						<h3 class="card-title">{{ __('Health check') }}</h3>
						<div class="card-actions">
							<span class="text-secondary">{{ __(':uptime uptime', ['uptime' => $percent($summary->uptime)]) }}</span>
						</div>
					</div>
					<div class="card-body">
						<div class="text-secondary mb-3">{{ trans_choice(':count check|:count checks', $summary->checks, ['count' => number_format($summary->checks)]) }} · {{ trans_choice(':count failed|:count failed', $summary->failed, ['count' => number_format($summary->failed)]) }}</div>
						<div class="tracking">
							@foreach ($buckets as $i => $bucket)
								<div @class(['tracking-block', $bucket_colours[$bucket->status], 'd-none d-md-block' => $i < $phone_start]) data-bs-toggle="tooltip" title="{{ $bucket->from->isoFormat('lll') }} – {{ $bucket_labels[$bucket->status] }}"></div>
							@endforeach
						</div>
					</div>
					<div class="card-footer">
						<div class="d-flex align-items-center text-secondary">
							<div>
								<span class="d-none d-md-inline">{{ $buckets->first()->from->diffForHumans(options: Carbon\CarbonInterface::ROUND) }}</span>
								<span class="d-md-none">{{ $buckets[$phone_start]->from->diffForHumans(options: Carbon\CarbonInterface::ROUND) }}</span>
							</div>
							<div class="legend-list mx-auto">
								<span class="legend legend-green"><span class="legend-dot"></span>{{ __('Up') }}</span>
								<span class="legend legend-yellow"><span class="legend-dot"></span>{{ __('Some checks failed') }}</span>
								<span class="legend legend-red"><span class="legend-dot"></span>{{ __('Down') }}</span>
							</div>
							<div>{{ __('Now') }}</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-4">
				<div class="card h-100">
					<div class="card-header">
						<h3 class="card-title">{{ __('Failed checks') }}</h3>
					</div>
					@if ($failures->isEmpty())
						<div class="card-body text-secondary">{{ __('No failed checks in the last 7 days.') }}</div>
					@else
						<div class="list-group list-group-flush">
							@foreach ($failures as $failure)
								<div class="list-group-item">
									<div class="row align-items-center">
										<div class="col-auto"><span class="status-dot status-red"></span></div>
										<div class="col text-truncate">
											<div class="fw-medium text-truncate" title="{{ $failure->error }}">{{ $failure->error }}</div>
											<div class="text-secondary text-truncate" title="{{ $failure->at }}">{{ $failure->at->diffForHumans() }} · {{ $ms($failure->duration) }}</div>
										</div>
									</div>
								</div>
							@endforeach
						</div>
					@endif
				</div>
			</div>

			<div class="col-lg-8">
				<div class="card h-100">
					<div class="card-body">
						<h3 class="card-title">{{ __('Response time (ms)') }}</h3>
						@if ($plotted->isEmpty())
							<div class="text-secondary">{{ __('No successful checks in this period.') }}</div>
						@else
							<div class="d-flex text-secondary small">
								<div>{{ $ms($slowest) }}</div>
							</div>
							<svg aria-label="{{ __('Response time (ms)') }}" class="w-100 text-primary" height="120" preserveAspectRatio="none" role="img" viewBox="0 0 100 40">
								<polyline fill="none" points="{{ $points }}" stroke="currentColor" stroke-width="2" vector-effect="non-scaling-stroke" />
							</svg>
							<div class="d-flex text-secondary small">
								<div>{{ $buckets->first()->from->diffForHumans(options: Carbon\CarbonInterface::ROUND) }}</div>
								<div class="ms-auto">{{ __('Now') }}</div>
							</div>
						@endif
					</div>
				</div>
			</div>
			<div class="col-lg-4">
				<div class="card h-100">
					<div class="card-body">
						<h3 class="card-title">{{ __('Cache') }}</h3>
						@if ($cache_total === 0)
							<div class="text-secondary">{{ __('No cache reads in this period.') }}</div>
						@else
							<div class="d-flex align-items-baseline mb-2">
								<div class="h1 mb-0 me-2">{{ $percent(100 * $cache->hits / $cache_total) }}</div>
								<div class="text-secondary">{{ __('hit rate') }}</div>
							</div>
							<div class="progress progress-separated mb-3">
								<div class="progress-bar bg-green" role="progressbar" style="width: {{ 100 * $cache->hits / $cache_total }}%" aria-label="{{ __('Hits') }}"></div>
								<div class="progress-bar bg-red" role="progressbar" style="width: {{ 100 * $cache->misses / $cache_total }}%" aria-label="{{ __('Misses') }}"></div>
							</div>
							<div class="legend-list">
								<span class="legend legend-green"><span class="legend-dot"></span>{{ __('Hits') }} {{ number_format($cache->hits) }}</span>
								<span class="legend legend-red"><span class="legend-dot"></span>{{ __('Misses') }} {{ number_format($cache->misses) }}</span>
							</div>
						@endif
					</div>
				</div>
			</div>

			<div class="col-12">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">{{ __('Availability') }}</h3>
					</div>
					<div class="card-table table-responsive">
						<table class="table table-vcenter">
							<thead>
								<tr>
									<th>{{ __('Time period') }}</th>
									<th>{{ __('Availability') }}</th>
									<th>{{ __('Checks') }}</th>
									<th>{{ __('Failed checks') }}</th>
									<th>{{ __('Average response') }}</th>
								</tr>
							</thead>
							<tbody>
								@foreach ($availability as $key => $row)
									<tr>
										<td>{{ $periods[$key] }}</td>
										<td class="fw-medium">{{ $percent($row->uptime) }}</td>
										<td class="text-secondary">{{ number_format($row->checks) }}</td>
										<td class="text-secondary">{{ number_format($row->failed) }}</td>
										<td class="text-secondary">{{ $ms($row->average) }}</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="col-lg-4">
				<div class="card h-100">
					<div class="card-header">
						<h3 class="card-title">{{ __('Most active users') }}</h3>
					</div>
					@if ($active_users->isEmpty())
						<div class="card-body text-secondary">{{ __('Nobody has used the site in this period.') }}</div>
					@else
						<div class="card-table table-responsive">
							<table class="table table-vcenter">
								<thead>
									<tr>
										<th>{{ __('User') }}</th>
										<th class="text-end">{{ __('Requests') }}</th>
										<th class="text-end">{{ __('Slow') }}</th>
									</tr>
								</thead>
								<tbody>
									@foreach ($active_users as $row)
										<tr>
											<td><x-user-entry :user="$row->user" /></td>
											<td class="text-end">{{ number_format($row->count) }}</td>
											<td class="text-end text-secondary">{{ number_format($row->slow) }}</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					@endif
				</div>
			</div>

			<div class="col-lg-8">
				<div class="card h-100">
					<div class="card-header">
						<div>
							<h3 class="card-title">{{ __('Slow queries') }}</h3>
							<p class="card-subtitle">{{ __('Database queries that took longer than :ms ms.', ['ms' => $threshold(Recorders\SlowQueries::class)]) }}</p>
						</div>
					</div>
					@if ($slow_queries->isEmpty())
						<div class="card-body text-secondary">{{ __('None in this period.') }}</div>
					@else
						<div class="card-table table-responsive">
							<table class="table table-vcenter">
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
												<div class="small text-secondary">{{ $slow_query->location }}</div>
											</td>
											<td class="text-end">{{ number_format($slow_query->count) }}</td>
											<td class="text-end text-nowrap">{{ $ms($slow_query->max) }}</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					@endif
				</div>
			</div>

			<div class="col-lg-6">
				<div class="card h-100">
					<div class="card-header">
						<h3 class="card-title">{{ __('Exceptions') }}</h3>
					</div>
					@if ($exceptions->isEmpty())
						<div class="card-body text-secondary">{{ __('No exceptions in this period.') }}</div>
					@else
						<div class="card-table table-responsive">
							<table class="table table-vcenter">
								<thead>
									<tr>
										<th>{{ __('Exception') }}</th>
										<th class="text-end">{{ __('Count') }}</th>
										<th class="text-end">{{ __('Latest') }}</th>
									</tr>
								</thead>
								<tbody>
									@foreach ($exceptions as $exception)
										<tr>
											<td class="text-break">
												<div class="font-monospace">{{ $exception->class }}</div>
												<div class="small text-secondary">{{ $exception->location }}</div>
											</td>
											<td class="text-end">{{ number_format($exception->count) }}</td>
											<td class="text-end text-nowrap" title="{{ $exception->latest }}">{{ $exception->latest->diffForHumans() }}</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					@endif
				</div>
			</div>

			<div class="col-lg-6">
				<div class="card h-100">
					<div class="card-header">
						<div>
							<h3 class="card-title">{{ __('Slow requests') }}</h3>
							<p class="card-subtitle">{{ __('Pages that took longer than :ms ms.', ['ms' => $threshold(Recorders\SlowRequests::class)]) }}</p>
						</div>
					</div>
					@if ($slow_requests->isEmpty())
						<div class="card-body text-secondary">{{ __('None in this period.') }}</div>
					@else
						<div class="card-table table-responsive">
							<table class="table table-vcenter">
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
												<div class="small text-secondary">{{ $slow_request->action }}</div>
											</td>
											<td class="text-end">{{ number_format($slow_request->count) }}</td>
											<td class="text-end text-nowrap">{{ $ms($slow_request->max) }}</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					@endif
				</div>
			</div>

			<div class="col-12">
				<div class="card">
					<div class="card-header">
						<div>
							<h3 class="card-title">{{ __('Slow outgoing requests') }}</h3>
							<p class="card-subtitle">{{ __('Requests to other services that took longer than :ms ms.', ['ms' => $threshold(Recorders\SlowOutgoingRequests::class)]) }}</p>
						</div>
					</div>
					@if ($slow_outgoing_requests->isEmpty())
						<div class="card-body text-secondary">{{ __('None in this period.') }}</div>
					@else
						<div class="card-table table-responsive">
							<table class="table table-vcenter">
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
											<td class="text-end text-nowrap">{{ $ms($outgoing->max) }}</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					@endif
				</div>
			</div>
		</div>
	</div>
</x-layout>
