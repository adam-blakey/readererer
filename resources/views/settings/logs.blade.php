@props(['file', 'level', 'entries'])

@php
	$filters = [
	    null => __('All'),
	    'info' => __('Info and above'),
	    'warning' => __('Warnings and above'),
	    'error' => __('Errors and above'),
	];
	$badge = fn (string $level) => match ($level) {
	    'debug' => 'bg-secondary-lt',
	    'info' => 'bg-green-lt',
	    'notice' => 'bg-azure-lt',
	    'warning' => 'bg-yellow-lt',
	    default => 'bg-red-lt',
	};
@endphp

<x-layout>
	<div class="container-xl">
		<div class="card">
			<div class="card-header flex-wrap gap-2">
				<div>
					<h3 class="card-title">{{ __('Application log') }}</h3>
					@if ($file)
						<p class="card-subtitle font-monospace">storage/logs/{{ $file }}</p>
					@endif
				</div>
				<div class="card-actions">
					<div aria-label="{{ __('Log level') }}" class="btn-group flex-wrap" role="group">
						@foreach ($filters as $key => $label)
							<a @class(['btn', 'active' => $level === ($key ?: null)]) href="{{ route('settings.monitoring.logs', array_filter(['level' => $key])) }}">{{ $label }}</a>
						@endforeach
					</div>
				</div>
			</div>
			@if ($entries->isEmpty())
				<div class="card-body text-secondary">{{ $file ? __('No log entries to show.') : __('There is no log file yet.') }}</div>
			@else
				<div class="list-group list-group-flush">
					@foreach ($entries as $entry)
						<div class="list-group-item">
							<div class="row g-2 align-items-baseline">
								<div class="col-auto text-secondary text-nowrap font-monospace small" title="{{ $entry->at }}">{{ $entry->at->isoFormat('lll') }}</div>
								<div class="col-auto"><span class="badge {{ $badge($entry->level) }}">{{ strtoupper($entry->level) }}</span></div>
								<div class="col text-break">
									{{ $entry->message }}
									@if ($entry->details !== '')
										<details class="mt-1">
											<summary class="text-secondary small">{{ __('Details') }}</summary>
											<pre class="mt-2 mb-0"><code>{{ $entry->details }}</code></pre>
										</details>
									@endif
								</div>
							</div>
						</div>
					@endforeach
				</div>
				<div class="card-footer text-secondary">{{ __('The latest :count entries, newest first.', ['count' => $entries->count()]) }}</div>
			@endif
		</div>
	</div>
</x-layout>
