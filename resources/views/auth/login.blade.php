@props(['page_name'])

<x-layout :$page_name :show_nav_menu="false" :show_page_header="false">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<div class="container py-4 container-tight">
					<div class="mb-4 text-center">
						<a class="autodark" href="{{ route('home') }}">
							<img src="{{ Vite::asset('resources/images/readererer-long-logo.svg') }}">
						</a>
					</div>
					<div class="card card-md">
						<div class="card-body">
							<h2 class="mb-4 text-center h2">Login to your account</h2>
							<form action="{{ route('login') }}" method="POST">
								@csrf

								<div class="mb-3">
									<label class="form-label" for="email">{{ __('Email address') }}</label>
									<input autocomplete="username" class="form-control" id="email" name="email" placeholder="your@email.com" required type="email" value="{{ old('email') }}">
									<x-forms.input-error :messages="$errors->get('email')" />
								</div>
								<div class="mb-2">
									<label class="form-label" for="password">
										{{ __('Password') }}
										<span class="form-label-description">
											<a href="{{ route('password.request') }}">{{ __('I forgot password') }}</a>
										</span>
									</label>
									<div class="input-group input-group-flat">
										<input autocomplete="current-password" class="form-control" id="password" name="password" placeholder="Your password" required type="password">
										<span class="input-group-text">
											<a aria-label="Show password" class="link-secondary" data-bs-original-title="Show password" data-bs-toggle="tooltip" href="#" onclick="togglePasswordVisibility('password')">
												<x-icon icon="eye" />
											</a>
										</span>
										<x-forms.input-error :messages="$errors->get('password')" />
									</div>
								</div>
								<div class="mb-2">
									<label class="form-check" for="remember_me">
										<input class="form-check-input" id="remember_me" name="remember_me" type="checkbox">
										<span class="form-check-label">{{ __('Remember me on this device') }}</span>
									</label>
								</div>
								<div class="form-footer">
									<button class="btn btn-primary w-100" type="submit">
										<x-icon icon="login" />
										{{ __('Login') }}
									</button>
								</div>
							</form>
						</div>
						<div class="hr-text">or</div>
						<div class="card-body">
							<div class="row">
								<div class="col">
									<a class="btn w-100" href="#">
										<x-icon icon="google" />
										Login with Google
									</a>
								</div>
							</div>
						</div>
					</div>
					<div class="mt-3 text-center text-secondary">
						Don't have account yet? <a href="./sign-up.html" tabindex="-1">Sign up</a>
					</div>
				</div>
			</div>
		</x-card-row>
	</div>
</x-layout>
