@props(['page_name'])

<x-layout :$page_name :show_nav_menu="false" :show_page_header="false">
	<div class="container py-4 container-tight">
		<div class="mb-4 text-center">
			<a class="navbar-brand navbar-brand-autodark" href=".">
				<img src="{{ Vite::asset('resources/images/readererer-long-logo.svg') }}">
			</a>
		</div>
		<form action="{{ route('password.email') }}" class="card card-md" method="POST">
			@csrf

			<div class="card-body">
				<h2 class="mb-4 text-center h2">Forgot password</h2>
				<p class="mb-4 text-secondary">Enter your email address and your password will be reset and emailed to you.</p>
				<div class="mb-3">
					<label class="form-label" for="email">Email address</label>
					<input class="form-control" id="email" name="email" placeholder="Enter email" required type="email" value="{{ old('email') }}">
					<x-forms.input-error :messages="$errors->get('email')" />
				</div>
				<div class="form-footer">
					<button class="btn btn-primary w-100" type="submit">
						<x-icon name="mail" />
						Send me new password
					</button>
				</div>
			</div>
		</form>
		<div class="mt-3 text-center text-secondary">
			Forget it, <a href="{{ route('login') }}">send me back</a> to the sign in screen.
		</div>
	</div>
</x-layout>
