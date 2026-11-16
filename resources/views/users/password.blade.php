@props(['user'])

<x-layout>
	<div class="page-body">
		<div class="container-xl">
			<div class="row">
				<div class="col-lg-6">
					<form action="{{ route('users.password', $user) }}" class="card" method="POST">
						@csrf
						@method('PUT')
						<div class="card-body">
							<x-forms.error-summary :fields="['password', 'password_confirmation']" />
							<p class="text-secondary">{{ __('Setting a password logs :name out on every device.', ['name' => $user->name]) }}</p>
							<div class="mb-3">
								<label class="form-label" for="password">{{ __('New password') }}</label>
								<input autocomplete="new-password" autofocus class="form-control" id="password" name="password" required type="password">
								<x-forms.input-error :messages="$errors->get('password')" />
							</div>
							<div class="mb-3">
								<label class="form-label" for="password_confirmation">{{ __('Confirm password') }}</label>
								<input autocomplete="new-password" class="form-control" id="password_confirmation" name="password_confirmation" required type="password">
								<x-forms.input-error :messages="$errors->get('password_confirmation')" />
							</div>
							<label class="form-check">
								<input @checked($user->role !== App\Enums\UserRole::Ensemble) class="form-check-input" name="must_change_password" type="checkbox" value="1">
								<span class="form-check-label">{{ __('Make them choose a new password when they next log in') }}</span>
							</label>
						</div>
						<div class="card-footer text-end">
							<x-a class="btn" :route="'users.show'" :user="$user">{{ __('Cancel') }}</x-a>
							<button class="btn btn-primary" type="submit">
								<x-icon name="key" />
								{{ __('Set password') }}
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</x-layout>
