@props(['user', 'roles', 'setupGroups', 'ensembles', 'allInstrumentFamilies', 'instrumentFamilies', 'page_name'])

<x-layout :$page_name :show_page_header="0">
	<div class="page-header">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-auto">
					<span class="rounded avatar avatar-lg" style="background-image: url({{ $user->image }})"></span>
				</div>
				<div class="col">
					<h1 class="my-0 font-bold">{{ $user->name }}</h1>
					<span class="badge bg-blue text-blue-fg">{{ $user->role_description }}</span>
				</div>
				<div class="col-auto ms-auto">
					<div class="btn-list">
						<x-a aria-label="Button" class="btn" :route="'users.show'" :user="$user">
							View
						</x-a>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="page-body">
		<div class="container-xl">
			@if (session('status'))
				<div class="alert alert-success" role="alert">{{ session('status') }}</div>
			@endif

			<div class="row g-3">
				<div class="col-lg-6">
					<div class="mb-3 card">
						<div class="card-header">
							<h2 class="mb-0 card-heading">Edit user details</h2>
						</div>
						<div class="card-body">
							<form action="{{ route('users.update', ['user' => $user]) }}" method="POST">
								@csrf
								@method('PATCH')

								<div class="row">
									<div class="col-md-6 mb-3">
										<label class="form-label required">First name</label>
										<input class="form-control @error('first_name') is-invalid @enderror" name="first_name" type="text" value="{{ old('first_name', $user->first_name) }}" required>
										@error('first_name')
											<div class="invalid-feedback">{{ $message }}</div>
										@enderror
									</div>
									<div class="col-md-6 mb-3">
										<label class="form-label required">Last name</label>
										<input class="form-control @error('last_name') is-invalid @enderror" name="last_name" type="text" value="{{ old('last_name', $user->last_name) }}" required>
										@error('last_name')
											<div class="invalid-feedback">{{ $message }}</div>
										@enderror
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label required">Email</label>
									<input class="form-control @error('email') is-invalid @enderror" name="email" type="email" value="{{ old('email', $user->email) }}" required>
									@error('email')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
								<div class="mb-3">
									<label class="form-label required">Role</label>
									<select name="role" class="form-select @error('role') is-invalid @enderror" required>
										@foreach ($roles as $role)
											<option value="{{ $role->value }}" {{ (int) old('role', $user->role->value) === $role->value ? 'selected' : '' }}>{{ $role->name }}</option>
										@endforeach
									</select>
									@error('role')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
								<div class="mb-3">
									<label class="form-label required">Setup group</label>
									<select name="setup_group" class="form-select @error('setup_group') is-invalid @enderror" required>
										<option value="">Select setup group</option>
										@foreach ($setupGroups as $setupGroup)
											<option value="{{ $setupGroup->id }}" {{ (int) old('setup_group', $user->setup_group_id) === $setupGroup->id ? 'selected' : '' }}>{{ $setupGroup->name }}</option>
										@endforeach
									</select>
									@error('setup_group')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>

								<div class="text-end">
									<button type="submit" class="btn btn-primary">Save details</button>
								</div>
							</form>
						</div>
					</div>
				</div>

				<div class="col-lg-6">
					<div class="mb-3 card">
						<div class="card-header">
							<h2 class="mb-0 card-heading">Ensembles ({{ $user->ensembles->count() }})</h2>
							<div class="card-actions">
								<a href="#" class="btn btn-primary btn-3" data-bs-toggle="modal" data-bs-target="#modal-add-ensemble-user">
									Add to ensemble
								</a>
							</div>
						</div>
						<div class="card-body">
							@forelse ($user->ensembles as $ensemble)
								@php
									$instrument = $instrumentFamilies[$ensemble->pivot->instrument_family_id]->name ?? '[none]';
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
										<form method="POST" action="{{ route('users.ensembles.detach', ['user' => $user, 'ensemble' => $ensemble]) }}" onsubmit="return confirm('Remove this user from {{ $ensemble->name }}?');">
											@csrf
											@method('DELETE')
											<button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
										</form>
									</div>
								</div>
							@empty
								<p class="text-muted mb-0">This user is not a member of any ensembles.</p>
							@endforelse
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-layout>

<x-modals.add-ensemble-user :user="$user" :ensembles="$ensembles" :instrument-families="$allInstrumentFamilies" />
