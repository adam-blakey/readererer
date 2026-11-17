@props(['page_name', 'preview', 'summary', 'configured', 'status'])

<x-layout>
	<div class="container-xl">
		@if (session('status'))
			<div class="alert alert-success" role="alert">{{ session('status') }}</div>
		@endif

		@if ($summary)
			<div class="card mb-3">
				<div class="card-body">
					<div class="alert alert-success" role="alert">{{ __('The Membermojo members have been imported.') }}</div>
					@include('settings.membermojo-report', ['report' => $summary])
				</div>
			</div>
		@endif

		@if ($preview)
			<form action="{{ route('settings.membermojo.store') }}" class="card mb-3" method="POST">
				@csrf
				<div class="card-header">
					<h2 class="card-title">{{ __('Preview') }}</h2>
				</div>
				<div class="card-body">
					<x-forms.error-summary :fields="['export']" />
					<p class="text-secondary">
						{{ $preview['overwrite'] ? __('Existing details will be overwritten.') : __('Only empty details will be filled in.') }}
						{{ $preview['archive_leavers'] ? __('Members who have left will be archived.') : __('Members who have left will be listed only.') }}
					</p>
					@include('settings.membermojo-report', ['report' => $preview['report']])
				</div>
				<div class="card-footer text-end">
					<button class="btn btn-primary" type="submit">{{ __('Import') }}</button>
				</div>
			</form>
		@endif

		<form action="{{ route('settings.membermojo.preview') }}" class="card mb-3" enctype="multipart/form-data" method="POST">
			@csrf
			<div class="card-header">
				<h2 class="card-title">{{ __('Upload a members download') }}</h2>
			</div>
			<div class="card-body">
				@unless ($preview)
					<x-forms.error-summary :fields="['export', 'overwrite', 'archive_leavers']" />
				@endunless
				<p class="text-secondary">{{ __('Upload the members.csv file downloaded from Membermojo to see what will be imported. Nothing is saved until you confirm the preview.') }}</p>
				<div class="mb-3">
					<label class="form-label required" for="export">{{ __('Membermojo members download') }}</label>
					<input accept="text/csv,.csv" class="form-control @error('export') is-invalid @enderror" id="export" name="export" required type="file">
					@error('export')
						<x-forms.input-error :messages="$errors->get('export')" />
					@enderror
				</div>
				<label class="form-check">
					<input checked class="form-check-input" name="overwrite" type="checkbox" value="1">
					<span class="form-check-label">{{ __('Overwrite existing details') }}</span>
					<span class="form-check-description">{{ __('When unticked, only empty details are filled in and new members are added.') }}</span>
				</label>
				<label class="form-check">
					<input class="form-check-input" name="archive_leavers" type="checkbox" value="1">
					<span class="form-check-label">{{ __('Archive members who have left') }}</span>
					<span class="form-check-description">{{ __('Archives users linked to Membermojo who are no longer active there. Admins are never archived.') }}</span>
				</label>
			</div>
			<div class="card-footer text-end">
				<button class="btn btn-primary" type="submit">{{ __('Preview import') }}</button>
			</div>
		</form>

		<div class="card">
			<div class="card-header">
				<h2 class="card-title">{{ __('Scheduled download') }}</h2>
			</div>
			<div class="card-body">
				<p class="text-secondary">{{ __('Readererer can sign in to Membermojo every day, download the members and import them. Members who have left are listed here and never archived. Use a read-only Membermojo admin account made just for this.') }}</p>

				@if ($status)
					<div class="alert {{ $status['ok'] ? 'alert-success' : 'alert-danger' }}" role="alert">
						<h4 class="alert-title">{{ $status['ok'] ? __('Last download worked') : __('Last download failed') }}</h4>
						<div class="text-secondary">{{ \Illuminate\Support\Carbon::parse($status['at'])->timezone(config('app.timezone'))->format('j F Y, '.time_format()) }}</div>
						@unless ($status['ok'])
							<div>{{ $status['error'] }}</div>
						@endunless
					</div>
					@if ($status['ok'])
						@include('settings.membermojo-report', ['report' => $status])
					@endif
				@endif

				@if ($configured)
					<form action="{{ route('settings.membermojo.sync') }}" class="mb-3" method="POST">
						@csrf
						<button class="btn" type="submit">
							<x-icon name="refresh" />
							{{ __('Download now') }}
						</button>
					</form>

					@if ($status && ! $status['ok'] || $errors->has('verification_link'))
						<form action="{{ route('settings.membermojo.verify') }}" class="mb-3" method="POST">
							@csrf
							<x-forms.error-summary :fields="['verification_link']" />
							<label class="form-label" for="verification_link">{{ __('Verification link') }}</label>
							<div class="input-group">
								<input class="form-control @error('verification_link') is-invalid @enderror" id="verification_link" name="verification_link" placeholder="{{ \App\Services\MembermojoClient::BASE_URL }}/…" required type="url" value="{{ old('verification_link') }}">
								<button class="btn" type="submit">{{ __('Verify and download') }}</button>
							</div>
							@error('verification_link')
								<x-forms.input-error :messages="$message" />
							@enderror
							<small class="form-hint">{{ __('The first download from a new server makes Membermojo email a browser verification link to the account. Paste it here without opening it; it only works once.') }}</small>
						</form>
					@endif
				@endif

				<form action="{{ route('settings.membermojo.update') }}" method="POST">
					@csrf
					@method('PUT')
					<x-forms.error-summary :fields="['membermojo_shortname', 'membermojo_email', 'membermojo_password', 'membermojo_time', 'membermojo_overwrite']" />
					<div class="row">
						<div class="col-md-4 mb-3">
							<label class="form-label" for="membermojo_shortname">{{ __('Membermojo short name') }}</label>
							<input autocomplete="off" class="form-control @error('membermojo_shortname') is-invalid @enderror" id="membermojo_shortname" name="membermojo_shortname" type="text" value="{{ old('membermojo_shortname', config('services.membermojo.shortname')) }}">
							@error('membermojo_shortname')
								<x-forms.input-error :messages="$message" />
							@enderror
							<small class="form-hint">{{ __('The part after membermojo.co.uk/ in your site\'s address.') }}</small>
						</div>
						<div class="col-md-4 mb-3">
							<label class="form-label" for="membermojo_email">{{ __('Membermojo email address') }}</label>
							<input autocomplete="off" class="form-control @error('membermojo_email') is-invalid @enderror" id="membermojo_email" name="membermojo_email" type="email" value="{{ old('membermojo_email', config('services.membermojo.email')) }}">
							@error('membermojo_email')
								<x-forms.input-error :messages="$message" />
							@enderror
						</div>
						<div class="col-md-4 mb-3">
							<label class="form-label" for="membermojo_password">{{ __('Membermojo password') }}</label>
							<input autocomplete="new-password" class="form-control @error('membermojo_password') is-invalid @enderror" id="membermojo_password" name="membermojo_password" type="password">
							@error('membermojo_password')
								<x-forms.input-error :messages="$message" />
							@enderror
							@if (filled(config('services.membermojo.password')))
								<small class="form-hint">{{ __('A password is saved. Leave this empty to keep it.') }}</small>
							@endif
						</div>
					</div>
					<div class="row">
						<div class="col-md-4 mb-3">
							<label class="form-label" for="membermojo_time">{{ __('Download time') }}</label>
							<x-forms.time-input id="membermojo_time" :invalid="$errors->has('membermojo_time')" name="membermojo_time" :value="old('membermojo_time', config('services.membermojo.time'))" />
							@error('membermojo_time')
								<x-forms.input-error :messages="$message" />
							@enderror
							<small class="form-hint">{{ __('When the members are downloaded each day, in the :timezone time zone.', ['timezone' => config('app.timezone')]) }}</small>
						</div>
					</div>
					<label class="form-check">
						<input class="form-check-input" name="membermojo_overwrite" type="checkbox" value="1" @checked(old('_method') ? old('membermojo_overwrite') : config('services.membermojo.overwrite'))>
						<span class="form-check-label">{{ __('Overwrite existing details') }}</span>
					</label>
					<div class="text-end">
						<button class="btn btn-primary" type="submit">{{ __('Save scheduled download') }}</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-layout>
