@props(['page_name', 'must_change_password'])

<x-layout :show_nav_menu="! $must_change_password" :show_page_header="false">
	<div class="container py-4 container-tight">
		<form action="{{ route('password.update') }}" class="card card-md" method="POST">
			@csrf
			@method('PUT')

			<div class="card-body">
				<h2 class="mb-3 text-center h2">{{ __('Change password') }}</h2>
				@if ($must_change_password)
					<p class="mb-4 text-center text-secondary">{{ __('You need to choose a new password before you can continue.') }}</p>
				@endif

				<x-forms.error-summary :fields="['current_password', 'password', 'password_confirmation']" />
				@if (session('status'))
					<div class="alert alert-success" role="alert">{{ session('status') }}</div>
				@endif

				@unless ($must_change_password)
					<div class="mb-3">
						<label class="form-label" for="current_password">{{ __('Current password') }}</label>
						<input autocomplete="current-password" autofocus class="form-control" id="current_password" name="current_password" required type="password">
						<x-forms.input-error :messages="$errors->get('current_password')" />
					</div>
				@endunless
				<div class="mb-3">
					<label class="form-label" for="password">{{ __('New password') }}</label>
					<input autocomplete="new-password" @if ($must_change_password) autofocus @endif class="form-control" id="password" name="password" required type="password">
					<x-forms.input-error :messages="$errors->get('password')" />
				</div>
				<div class="mb-3">
					<label class="form-label" for="password_confirmation">{{ __('Confirm password') }}</label>
					<input autocomplete="new-password" class="form-control" id="password_confirmation" name="password_confirmation" required type="password">
					<x-forms.input-error :messages="$errors->get('password_confirmation')" />
				</div>
				<div class="form-footer">
					<button class="btn btn-primary w-100" type="submit">{{ __('Change password') }}</button>
				</div>
			</div>
		</form>
		@if ($must_change_password)
			<form action="{{ route('logout') }}" class="mt-3 text-center" method="POST">
				@csrf
				<button class="btn btn-link" type="submit">{{ __('Log out') }}</button>
			</form>
		@endif
	</div>
</x-layout>
