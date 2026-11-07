@props(['user', 'instrument_families'])

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
                    <x-a aria-label="{{ __('Edit') }}" class="btn" :route="'users.edit'" :user="$user">
                        <x-icon name="pencil" />
                        {{ __('Edit') }}
                    </x-a>
                    @can('resetPassword', $user)
                        <a href="#" class="btn" data-bs-toggle="modal" data-bs-target="#modal-reset-password">
                            <x-icon name="key" />
                            {{ __('Reset password') }}
                        </a>
                    @endcan
                    @can('erase', $user)
                        <a href="#" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal-erase-user">
                            <x-icon name="trash" />
                            {{ __('Delete') }}
                        </a>
                    @endcan
                </div>
            </div>
        </div>
        @if (session('status'))
            <div class="mt-3 alert alert-success" role="alert">{{ session('status') }}</div>
        @endif
    </div>

	<div class="page-body">
		<div class="container-xl">
			<div class="row g-3">
				<div class="col-lg-4">
					<div class="mb-3 card">
						<div class="card-header">
							<h2 class="mb-0 card-heading">{{ __('Ensembles (:count)', ['count' => $user->ensembles->count()]) }}</h2>
						</div>
						<div class="card-body">
							@foreach ($user->ensembles as $ensemble)
 							<p>
 								<x-a :route="'ensembles.show'" :ensemble="$ensemble">{{ $ensemble->name }}</x-a>: <strong>{{ $instrumentFamilies[$ensemble->pivot->instrument_family_id]->name ?? __('[none]') }} {{ ($ensemble->pivot->seat_column == null or $ensemble->pivot->seat_row == null) ? '' : '(' . $ensemble->pivot->seat_column . $ensemble->pivot->seat_row . ')' }}</strong>
 							</p>
							@endforeach
						</div>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="row row-cards">
						<div class="col-12">
							<div class="card">
								<div class="card-header">
									<h2 class="mb-0 card-heading">{{ __('Personal details') }}</h2>
								</div>
								<div class="card-body">
									<div class="mb-2">
										<x-icon name="user" />
										{{ __('Name') }}:
										<strong>{{ $user->name }}</strong>
									</div>
                                    <div class="mb-2">
                                        <x-icon name="keyboard" />
                                        {{ __('Username') }}:
                                        <strong>{{ $user->username }}</strong>
                                    </div>
									<div class="mb-2">
										<x-icon name="mail" />
										{{ __('Email') }}:
										<strong>{{ $user->email }}</strong>
									</div>
									<div class="mb-2">
										<x-icon name="phone" />
										{{ __('Phone number') }}:
										<strong>{{ $user->phone_number }}</strong>
									</div>
									<div class="mb-2">
										<x-icon name="pin" />
										{{ __('Address') }}:
										<strong>{{ $user->full_address }}</strong>
									</div>
									<div class="mb-2">
										<x-icon name="building-hospital" />
										{{ __('Emergency contact details') }}:
										<strong>{{ $user->emergency_contact_details }}</strong>
									</div>
									<div class="mb-2">
										<x-icon name="cake" />
										{{ __('Date of birth') }}:
										<strong>{{ $user->date_of_birth?->format('j F Y') }}</strong>
									</div>
									<div class="mb-2">
										<x-icon name="glass" />
										{{ __('Over 18') }}:
										<strong>{{ $user->is_over_18 ? __('Yes') : __('No') }}</strong>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="row row-cards">
						<div class="col-12">
							<div class="card">
								<div class="card-header">
									<h2 class="mb-0 card-heading">{{ __('Additional info') }}</h2>
								</div>
								<div class="card-body">
									@if (config('app.readererer_setup_groups'))
									<div class="mb-2">
										<x-icon name="users-group" />
										{{ __('Setup group') }}:
										<strong>
											@if ($user->setup_group)
												<x-a :route="'setupgroups.show'" :setupGroup="$user->setup_group">{{ $user->setup_group->name }}</x-a>
											@else
												{{ __('No setup group') }}
											@endif
										</strong>
									</div>
									@if (config('app.readererer_van_drivers'))
									<div class="mb-2">
										<x-icon name="truck" />
										{{ __('Van driver for') }}:
										<strong>{{ $user->van_driver_groups->pluck('name')->join(', ') ?: __('None') }}</strong>
									</div>
									@endif
									@endif
									<div class="mb-2">
										<x-icon name="camera" />
										{{ __('Photograph permission') }}:
										<strong>{{ $user->has_photo_permission ? __('Yes') : __('No') }}</strong>
									</div>
									<div class="mb-2">
										<x-icon name="gift" />
										{{ __('Gift aid subs') }}:
										<strong>{{ $user->is_gift_aiding_subs ? __('Yes') : __('No') }}</strong>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
    @can('resetPassword', $user)
        <x-modals.reset-password :user="$user" />
    @endcan
    @can('erase', $user)
        <x-modals.erase-user :user="$user" />
    @endcan
</x-layout>
