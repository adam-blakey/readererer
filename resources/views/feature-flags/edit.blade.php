@props(['page_name', 'flags', 'ensembles'])

@php
	use App\Enums\UserRole;
	use Laravel\Pennant\Feature;

	$roles = collect(UserRole::cases())->mapWithKeys(fn (UserRole $role) => [$role->value => $role->label()])->all();
	$fields = ['flags', 'flags.*'];
	foreach (['enabled', 'roles', 'roles.*', 'ensembles', 'ensembles.*', 'starts_at', 'from_version'] as $option) {
		$fields[] = 'flags.*.'.$option;
	}
@endphp

<x-layout>
	<div class="container-xl">
		<form action="{{ route('feature-flags.update') }}" class="card" method="POST">
			@csrf
			@method('PUT')

			<div class="card-body">
				<x-forms.error-summary :fields="$fields" />
				@if (session('status'))
					<div class="alert alert-success" role="alert">{{ session('status') }}</div>
				@endif

				@forelse ($flags as $class => $flag)
					@php
						$options = $flag::options();
						$input = fn (string $option) => "flags[{$class}][{$option}]";
						$key = fn (string $option) => "flags.{$class}.{$option}";
						$id = 'flag-'.$loop->index;
					@endphp
					<div @class(['pt-4 mt-4 border-top' => ! $loop->first])>
						<div class="d-flex align-items-center mb-1">
							<h2 class="mb-0 me-2">{{ $flag->title() }}</h2>
							@if (Feature::active($class))
								<span class="badge bg-green-lt">{{ __('On for you') }}</span>
							@else
								<span class="badge bg-secondary-lt">{{ __('Off for you') }}</span>
							@endif
						</div>
						<p class="text-secondary">{{ $flag->description() }}</p>

						<div class="mb-3">
							<input name="{{ $input('enabled') }}" type="hidden" value="0">
							<label class="form-check form-switch">
								<input @class(['form-check-input', 'is-invalid' => $errors->has($key('enabled'))]) name="{{ $input('enabled') }}" type="checkbox" value="1" @checked(old($key('enabled'), $options['enabled']))>
								<span class="form-check-label">{{ __('Turned on') }}</span>
							</label>
						</div>

						<div class="row g-3">
							<div class="col-md-6 col-xl-3">
								<div class="form-label">{{ __('Roles') }}</div>
								<x-forms.multi-select :label="__('Roles')" :name="$input('roles')" :options="$roles" :placeholder="__('Every role')" :selected="old($key('roles'), $options['roles'])" />
								<div class="form-hint">{{ __('Only users with these roles get the feature.') }}</div>
								@error($key('roles').'*')
									<x-forms.input-error :messages="$message" />
								@enderror
							</div>
							@if (count($ensembles) > 1)
								<div class="col-md-6 col-xl-3">
									<div class="form-label">{{ __('Ensembles') }}</div>
									<x-forms.multi-select :label="__('Ensembles')" :name="$input('ensembles')" :options="$ensembles" :placeholder="__('Every ensemble')" :selected="old($key('ensembles'), $options['ensembles'])" />
									<div class="form-hint">{{ __('Only members of these ensembles get the feature.') }}</div>
									@error($key('ensembles').'*')
										<x-forms.input-error :messages="$message" />
									@enderror
								</div>
							@endif
							<div class="col-md-6 col-xl-3">
								<label class="form-label" for="{{ $id }}-starts_at">{{ __('Starts at') }}</label>
								<input @class(['form-control', 'is-invalid' => $errors->has($key('starts_at'))]) id="{{ $id }}-starts_at" name="{{ $input('starts_at') }}" type="datetime-local" value="{{ old($key('starts_at'), $options['starts_at']) }}">
								<div class="form-hint">{{ __('Leave empty to start straight away.') }}</div>
								@error($key('starts_at'))
									<x-forms.input-error :messages="$message" />
								@enderror
							</div>
							<div class="col-md-6 col-xl-3">
								<label class="form-label" for="{{ $id }}-from_version">{{ __('From release') }}</label>
								<input @class(['form-control', 'is-invalid' => $errors->has($key('from_version'))]) id="{{ $id }}-from_version" name="{{ $input('from_version') }}" placeholder="v1.5.0" type="text" value="{{ old($key('from_version'), $options['from_version']) }}">
								<div class="form-hint">{{ __('Turns on once this release is deployed. This site is on :version.', ['version' => config('app.version')]) }}</div>
								@error($key('from_version'))
									<x-forms.input-error :messages="$message" />
								@enderror
							</div>
						</div>
					</div>
				@empty
					<p class="text-secondary mb-0">{{ __('There are no feature flags yet.') }}</p>
				@endforelse
			</div>
			@if (filled($flags))
				<div class="card-footer text-end">
					<button class="btn btn-primary" type="submit">{{ __('Save feature flags') }}</button>
				</div>
			@endif
		</form>
	</div>
</x-layout>
