@props(['page_name', 'token', 'email'])

<x-layout :show_nav_menu="false" :show_page_header="false">
	<div class="container py-4 container-tight">
		<div class="mb-4 text-center">
			<a class="navbar-brand navbar-brand-autodark" href="{{ route('home') }}">
				<img src="{{ logo_url() }}">
			</a>
		</div>
		{{-- A button rather than logging in on the GET, so email link scanners cannot use up the link. --}}
		<form action="{{ route('login-link.login', ['token' => $token]) }}" class="card card-md" method="POST">
			@csrf

			<input name="email" type="hidden" value="{{ $email }}">

			<div class="card-body">
				<h2 class="mb-4 text-center h2">{{ __('Login to your account') }}</h2>
				<x-forms.error-summary :fields="[]" />
				<p class="mb-4 text-secondary">{{ __('Log in as :email.', ['email' => $email]) }}</p>
				<div class="form-footer">
					<button class="btn btn-primary w-100" type="submit">
						<x-icon name="login" />
						{{ __('Log in') }}
					</button>
				</div>
			</div>
		</form>
	</div>
</x-layout>
