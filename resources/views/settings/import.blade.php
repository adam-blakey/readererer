@props(['page_name', 'preview', 'summary', 'site_is_empty'])

<x-layout>
	<div class="container-xl">
		@if ($summary)
			<div class="card mb-3">
				<div class="card-body">
					<div class="alert alert-success" role="alert">{{ __('The Pollererer data has been imported.') }}</div>
					@include('settings.import-report', ['report' => $summary])
				</div>
			</div>
		@endif

		@if ($preview)
			<form action="{{ route('settings.import.store') }}" class="card mb-3" method="POST">
				@csrf
				<div class="card-header">
					<h2 class="card-title">{{ __('Preview') }}</h2>
				</div>
				<div class="card-body">
					<x-forms.error-summary :fields="['export', 'acknowledge_timezone']" />
					<p class="text-secondary">
						{{ __('Exported :date.', ['date' => $preview['exported_at']]) }}
						@if ($preview['anonymised'])
							{{ __('This export is anonymised.') }}
						@endif
					</p>
					@if ($preview['timezone_warning'])
						<div class="alert alert-danger" role="alert">
							<h4 class="alert-title">{{ __('Check the timezone') }}</h4>
							{{ $preview['timezone_warning'] }}
						</div>
					@endif
					@include('settings.import-report', ['report' => $preview])
					@if ($preview['images'])
						<h3>{{ __('Images to download') }}</h3>
						<ul>
							@foreach ($preview['images'] as $url)
								<li>{{ $url }}</li>
							@endforeach
						</ul>
					@endif
					@if ($preview['ignored_settings'])
						<h3>{{ __('Settings not imported') }}</h3>
						<p>{{ implode(', ', $preview['ignored_settings']) }}</p>
					@endif
					@if ($preview['timezone_warning'])
						<label class="form-check">
							<input class="form-check-input @error('acknowledge_timezone') is-invalid @enderror" name="acknowledge_timezone" type="checkbox" value="1">
							<span class="form-check-label">{{ __('I have checked the timezone and want to import these times anyway') }}</span>
						</label>
						@error('acknowledge_timezone')
							<x-forms.input-error :messages="$message" />
						@enderror
					@endif
				</div>
				<div class="card-footer text-end">
					<button class="btn btn-primary" type="submit">{{ __('Import') }}</button>
				</div>
			</form>
		@endif

		<form action="{{ route('settings.import.preview') }}" class="card" enctype="multipart/form-data" method="POST">
			@csrf
			<div class="card-body">
				@unless ($preview)
					<x-forms.error-summary :fields="['export']" />
				@endunless
				@unless ($site_is_empty)
					<div class="alert alert-info" role="alert">{{ __('Pollererer data can only be imported into a site with no ensembles, terms, dates or attendance.') }}</div>
				@endunless
				<p class="text-secondary">{{ __('Upload the JSON file from Pollererer\'s export to see what will be imported. Nothing is saved until you confirm the preview.') }}</p>
				<div class="mb-3">
					<label class="form-label required" for="export">{{ __('Pollererer export') }}</label>
					<input accept="application/json,.json" class="form-control @error('export') is-invalid @enderror" id="export" name="export" required type="file">
					@error('export')
						<x-forms.input-error :messages="$errors->get('export')" />
					@enderror
				</div>
			</div>
			<div class="card-footer text-end">
				<button class="btn btn-primary" type="submit">{{ __('Preview import') }}</button>
			</div>
		</form>
	</div>
</x-layout>
