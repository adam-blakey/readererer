@props(['page_name'])

<x-layout :show_page_header="false">
	<div class="container py-4 container-tight">
		<form action="{{ route('password.confirm') }}" class="card card-md" method="POST">
			@csrf

			<div class="card-body">
				<h2 class="mb-3 text-center h2">{{ __('Confirm password') }}</h2>
				<p class="mb-4 text-center text-secondary">{{ __('Please confirm your password before continuing.') }}</p>

				<x-forms.error-summary :fields="['password']" />

				<div class="mb-3">
					<label class="form-label" for="password">
						{{ __('Password') }}
						<span class="form-label-description">
							<x-a :route="'password.request'">{{ __('I forgot password') }}</x-a>
						</span>
					</label>
					<input autocomplete="current-password" autofocus class="form-control" id="password" name="password" required type="password">
					<x-forms.input-error :messages="$errors->get('password')" />
				</div>
				<div class="form-footer">
					<button class="btn btn-primary w-100" type="submit">{{ __('Confirm') }}</button>
				</div>
			</div>
		</form>
	</div>
</x-layout>
