@props(['page_name'])

<x-layout :$page_name :show_nav_menu="false" :show_page_header="false">
	<div class="container py-4 container-tight">
		<div class="mb-4 text-center">
			<a class="navbar-brand navbar-brand-autodark" href="{{ route('home') }}">
				<img src="{{ Vite::asset('resources/images/readererer-long-logo.svg') }}">
			</a>
		</div>
		<form action="{{ route('password.store') }}" class="card card-md" method="POST">
			@csrf

			<input name="token" type="hidden" value="{{ $request->route('token') }}">

			<div class="card-body">
				<h2 class="mb-4 text-center h2">Reset your password</h2>
				<div class="mb-3">
					<label class="form-label" for="email">Email address</label>
					<input autocomplete="username" autofocus class="form-control" id="email" name="email" placeholder="Enter email" required type="email" value="{{ old('email', $request->email) }}">
				</div>
				<div class="mb-3">
					<label class="form-label" for="password">Password</label>
					<div class="input-group input-group-flat">
						<input autocomplete="new-password" class="form-control" id="password" name="password" placeholder="Password" required type="password">
						<span class="input-group-text">
							<a aria-label="Show password" class="link-secondary" data-bs-original-title="Show password" data-bs-toggle="tooltip" href="#"><!-- Download SVG icon from http://tabler.io/icons/icon/eye -->
								<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
									<path d="M0 0h24v24H0z" fill="none" stroke="none"></path>
									<path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
									<path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path>
								</svg>
							</a>
						</span>
					</div>
				</div>
				<div class="mb-3">
					<label class="form-label" for="password">Confirm password</label>
					<div class="input-group input-group-flat">
						<input autocomplete="new-password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Password" required type="password">
						<span class="input-group-text">
							<a aria-label="Show password" class="link-secondary" data-bs-original-title="Show password" data-bs-toggle="tooltip" href="#"><!-- Download SVG icon from http://tabler.io/icons/icon/eye -->
								<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
									<path d="M0 0h24v24H0z" fill="none" stroke="none"></path>
									<path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
									<path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path>
								</svg>
							</a>
						</span>
					</div>
				</div>
				<div class="form-footer">
					<button class="btn btn-primary w-100" type="submit">Reset password</button>
				</div>
			</div>
		</form>
	</div>
</x-layout>
