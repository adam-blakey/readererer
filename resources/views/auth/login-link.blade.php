@props(['page_name'])

<x-layout :show_nav_menu="false" :show_page_header="false">
	<div class="container py-4 container-tight">
		<div class="mb-4 text-center">
			<a class="navbar-brand navbar-brand-autodark" href="{{ route('home') }}">
				<img src="{{ logo_url() }}">
			</a>
		</div>
		<form action="{{ route('login-link.email') }}" class="card card-md" method="POST">
			@csrf

			<div class="card-body">
				<h2 class="mb-4 text-center h2">{{ __('Email me a login link') }}</h2>
				<x-forms.error-summary :fields="['email']" />
				@if (session('status'))
					<div class="alert alert-success" role="alert">{{ session('status') }}</div>
				@endif
				<p class="mb-4 text-secondary">{{ __('Enter your email address and we will email you a link that logs you in without a password. Shared ensemble logins cannot log in by email.') }}</p>
				<div class="mb-3">
					<label class="form-label" for="email">{{ __('Email address') }}</label>
					<input autocomplete="email" autofocus class="form-control" id="email" name="email" placeholder="{{ __('Enter email') }}" required type="email" value="{{ old('email') }}">
					<x-forms.input-error :messages="$errors->get('email')" />
				</div>
				<div class="form-footer">
					<button class="btn btn-primary w-100" type="submit">
						<x-icon name="mail" />
						{{ __('Send me a login link') }}
					</button>
				</div>
			</div>
		</form>
		<div class="mt-3 text-center text-secondary">
			{!! __('Forget it, :link to the sign in screen.', ['link' => '<a href="'.route('login').'">'.e(__('send me back')).'</a>']) !!}
		</div>
	</div>
</x-layout>
