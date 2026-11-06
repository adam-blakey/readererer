@props(['user', 'roles', 'setupGroups', 'ensembles', 'allInstrumentFamilies', 'instrumentFamilies'])

<x-layout>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-auto">
                <span class="rounded avatar avatar-lg" style="background-image: url({{ $user->image_url }})"></span>
            </div>
            <div class="col">
                <h1 class="my-0 font-bold">{{ $user->name }}</h1>
                <span class="badge bg-blue text-blue-fg">{{ $user->role_description }}</span>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <x-a aria-label="{{ __('View') }}" class="btn" :route="'users.show'" :user="$user">
                        {{ __('View') }}
                    </x-a>
                </div>
            </div>
        </div>
    </div>

	<div class="page-body">
		<div class="container-xl">
			@if (session('status'))
				<div class="alert alert-success" role="alert">{{ session('status') }}</div>
			@endif

			<x-forms.error-summary :fields="['first_name', 'last_name', 'email', 'role', 'setup_group', 'phone_number', 'address_*', 'emergency_contact_*', 'date_of_birth', 'has_photo_permission', 'is_gift_aiding_subs']" />

			<div class="row g-3">
				<div class="col-lg-6">
					<div class="mb-3 card">
						<div class="card-header">
							<h2 class="mb-0 card-heading">{{ __('Edit user details') }}</h2>
						</div>
						<div class="card-body">
							<form action="{{ route('users.update', ['user' => $user]) }}" enctype="multipart/form-data" method="POST" data-dirty-check>
								@csrf
								@method('PATCH')

								<div class="row">
									<div class="col-md-6 mb-3">
										<label class="form-label required">{{ __('First name') }}</label>
										<input class="form-control @error('first_name') is-invalid @enderror" name="first_name" type="text" value="{{ old('first_name', $user->first_name) }}" required>
										@error('first_name')
											<div class="invalid-feedback">{{ $message }}</div>
										@enderror
									</div>
									<div class="col-md-6 mb-3">
										<label class="form-label required">{{ __('Last name') }}</label>
										<input class="form-control @error('last_name') is-invalid @enderror" name="last_name" type="text" value="{{ old('last_name', $user->last_name) }}" required>
										@error('last_name')
											<div class="invalid-feedback">{{ $message }}</div>
										@enderror
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label required">{{ __('Email') }}</label>
									<input class="form-control @error('email') is-invalid @enderror" name="email" type="email" value="{{ old('email', $user->email) }}" required>
									@error('email')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
								<div class="mb-3">
									<label class="form-label required">{{ __('Role') }}</label>
									<select name="role" class="form-select @error('role') is-invalid @enderror" required>
										@foreach ($roles as $role)
											<option value="{{ $role->value }}" {{ (int) old('role', $user->role->value) === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
										@endforeach
									</select>
									@error('role')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
								@if (config('app.readererer_setup_groups'))
								<div class="mb-3">
									<label class="form-label">{{ __('Setup group') }}</label>
									<select name="setup_group" class="form-select @error('setup_group') is-invalid @enderror">
										<option value="" @selected(! old('setup_group', $user->setup_group_id))>{{ __('No setup group') }}</option>
										@foreach ($setupGroups as $setupGroup)
											<option value="{{ $setupGroup->id }}" {{ (int) old('setup_group', $user->setup_group_id) === $setupGroup->id ? 'selected' : '' }}>{{ $setupGroup->name }}</option>
										@endforeach
									</select>
									@error('setup_group')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
								@endif

								<hr />
								@php
									$textFields = [
										'phone_number' => ['Phone number', 'tel'],
										'date_of_birth' => ['Date of birth', 'date'],
										'address_line1' => ['Address line 1', 'text'],
										'address_line2' => ['Address line 2', 'text'],
										'address_city' => ['City', 'text'],
										'address_post_code' => ['Post code', 'text'],
										'emergency_contact_name' => ['Emergency contact name', 'text'],
										'emergency_contact_number' => ['Emergency contact number', 'tel'],
										'emergency_contact_relationship' => ['Emergency contact relationship', 'text'],
										'emergency_contact_address_line1' => ['Emergency contact address line 1', 'text'],
										'emergency_contact_address_line2' => ['Emergency contact address line 2', 'text'],
										'emergency_contact_address_city' => ['Emergency contact city', 'text'],
										'emergency_contact_address_post_code' => ['Emergency contact post code', 'text'],
									];
								@endphp
								@foreach ($textFields as $name => [$label, $type])
									<div class="mb-3">
										<label class="form-label">{{ __($label) }}</label>
										<input class="form-control @error($name) is-invalid @enderror" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $name === 'date_of_birth' ? $user->date_of_birth?->format('Y-m-d') : $user->{$name}) }}">
										@error($name)
											<div class="invalid-feedback">{{ $message }}</div>
										@enderror
									</div>
								@endforeach
								@foreach (['has_photo_permission' => 'Photograph permission', 'is_gift_aiding_subs' => 'Gift aid subs'] as $name => $label)
									<div class="mb-3">
										<input type="hidden" name="{{ $name }}" value="0">
										<label class="form-check">
											<input class="form-check-input" type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $user->{$name}))>
											<span class="form-check-label">{{ __($label) }}</span>
										</label>
									</div>
								@endforeach

								<hr />
								<x-forms.image-upload :model="$user" :label="__('Profile picture')" />

								<div class="text-end">
									<button type="submit" class="btn btn-primary">{{ __('Save details') }}</button>
								</div>
							</form>
						</div>
					</div>
				</div>

				<div class="col-lg-6">
					<div class="mb-3 card">
						<div class="card-header">
							<h2 class="mb-0 card-heading">{{ __('Ensembles (:count)', ['count' => $user->ensembles->count()]) }}</h2>
							<div class="card-actions">
								<a href="#" class="btn btn-primary btn-3" data-bs-toggle="modal" data-bs-target="#modal-add-ensemble-user">
									{{ __('Add to ensemble') }}
								</a>
							</div>
						</div>
						<div class="card-body">
							@forelse ($user->ensembles as $ensemble)
								@php
									$instrument = $instrumentFamilies[$ensemble->pivot->instrument_family_id]->name ?? __('[none]');
									$seat = ($ensemble->pivot->seat_column == null || $ensemble->pivot->seat_row == null)
										? ''
										: ' (' . $ensemble->pivot->seat_column . $ensemble->pivot->seat_row . ')';
								@endphp
								<div class="p-1 nav-link d-flex lh-1 text-reset align-items-center">
									<div class="ps-2">
										<div><x-a :route="'ensembles.show'" :ensemble="$ensemble">{{ $ensemble->name }}</x-a></div>
										<div class="mt-1 small text-muted">{{ $instrument }}{{ $seat }}</div>
									</div>
									<div class="ms-auto align-self-center">
										<form method="POST" action="{{ route('users.ensembles.destroy', ['user' => $user, 'ensemble' => $ensemble]) }}" onsubmit="return confirm('{{ __('Remove this user from :name?', ['name' => $ensemble->name]) }}');">
											@csrf
											@method('DELETE')
											<button type="submit" class="btn btn-outline-danger btn-sm">{{ __('Remove') }}</button>
										</form>
									</div>
								</div>
							@empty
								<p class="text-muted mb-0">{{ __('This user is not a member of any ensembles.') }}</p>
							@endforelse
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-layout>

<x-modals.add-ensemble-user :user="$user" :ensembles="$ensembles" :instrument-families="$allInstrumentFamilies" />
