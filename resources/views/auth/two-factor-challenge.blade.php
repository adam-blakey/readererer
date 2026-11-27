@props(['page_name'])

<x-layout :show_nav_menu="false" :show_page_header="false">
	<div class="container py-4 container-tight">
		<form action="{{ route('two-factor.verify') }}" class="card card-md" method="POST">
			@csrf

			<div class="card-body">
				<h2 class="mb-3 text-center h2">{{ __('Two-factor authentication') }}</h2>
				<p class="mb-4 text-center text-secondary">{{ __('Enter the code from your authenticator app, or one of your recovery codes.') }}</p>

				<x-forms.error-summary :fields="['code']" />

				<div class="mb-3">
					<label class="form-label" for="code">{{ __('Code') }}</label>
					<input autocomplete="one-time-code" autofocus class="form-control" id="code" name="code" required type="text">
					<x-forms.input-error :messages="$errors->get('code')" />
				</div>
				<div class="form-footer">
					<button class="btn btn-primary w-100" type="submit">{{ __('Continue') }}</button>
				</div>
			</div>
		</form>
		<form action="{{ route('logout') }}" class="mt-3 text-center" method="POST">
			@csrf
			<button class="btn btn-link" type="submit">{{ __('Log out') }}</button>
		</form>
	</div>
</x-layout>
