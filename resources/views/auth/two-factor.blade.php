@props(['page_name', 'enabled', 'secret', 'qr_code', 'recovery_codes'])

<x-layout :show_page_header="false">
	<div class="container py-4 container-tight">
		<form action="{{ route($enabled ? 'two-factor.destroy' : 'two-factor.store') }}" class="card card-md" method="POST">
			@csrf
			@if ($enabled)
				@method('DELETE')
			@endif

			<div class="card-body">
				<h2 class="mb-3 text-center h2">{{ __('Two-factor authentication') }}</h2>

				<x-forms.error-summary :fields="['code']" />
				@if (session('status'))
					<div class="alert alert-success" role="alert">{{ session('status') }}</div>
				@endif

				@if ($recovery_codes)
					<div class="alert alert-warning" role="alert">
						<h4 class="alert-title">{{ __('Save your recovery codes') }}</h4>
						<div class="text-secondary">{{ __('Keep these codes somewhere safe. Each one logs you in once if you lose your authenticator app. They will not be shown again.') }}</div>
						<ul class="mt-2 mb-0 list-unstyled font-monospace">
							@foreach ($recovery_codes as $recovery_code)
								<li>{{ $recovery_code }}</li>
							@endforeach
						</ul>
					</div>
				@endif

				@if ($enabled)
					<p class="text-secondary">{{ __('Logging in asks for a code from your authenticator app. To turn this off, enter a code from the app or one of your recovery codes.') }}</p>
				@else
					<p class="text-secondary">{{ __('Scan this QR code with an authenticator app, such as Google Authenticator or Microsoft Authenticator, then enter the code it shows.') }}</p>
					<div class="mb-3 text-center">{!! $qr_code !!}</div>
					<p class="text-center text-secondary">
						{{ __('Or enter this key in the app:') }}
						<code class="user-select-all">{{ $secret }}</code>
					</p>
				@endif

				<div class="mb-3">
					<label class="form-label" for="code">{{ __('Code') }}</label>
					<input autocomplete="one-time-code" class="form-control" id="code" name="code" required type="text">
					<x-forms.input-error :messages="$errors->get('code')" />
				</div>
				<div class="form-footer">
					<button class="btn w-100 {{ $enabled ? 'btn-danger' : 'btn-primary' }}" type="submit">{{ $enabled ? __('Turn off') : __('Turn on') }}</button>
				</div>
			</div>
		</form>
	</div>
</x-layout>
