@props(['user', 'page_name'])

@php
	$terms = App\Models\Term::all()->sortBy('earliest_date');
@endphp

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
						<a aria-label="Button" class="btn" href="{{ route('users.edit', ['user' => $user]) }}">
							<x-icon name="pencil" />
							Edit
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="page-body">
		<div class="container-xl">
			<div class="row g-3">
				<div class="col">
					<div class="mb-3 card">
						<div class="card-header">
							<h2 class="mb-0 card-heading">Nottingham Symphonic Wind Orchestra</h2>
						</div>
						<div class="card-body">

						</div>
					</div>
					<div class="mb-3 card">
						<div class="card-header">
							<h2 class="mb-0 card-heading">Nottingham Wind Ensemble</h2>
						</div>
						<div class="card-body">

						</div>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="row row-cards">
						<div class="col-12">
							<div class="card">
								<div class="card-body">
									<div class="card-title">Personal details</div>
									<div class="mb-2">
										<x-icon name="user" />
										Name:
										<strong>{{ $user->name }}</strong>
									</div>
									<div class="mb-2">
										<x-icon name="mail" />
										Email:
										<strong>{{ $user->email }}</strong>
									</div>
									<div class="mb-2">
										<x-icon name="phone" />
										Phone number:
										<strong>{{ $user->phone_number }}</strong>
									</div>
									<div class="mb-2">
										<x-icon name="pin" />
										Address:
										<strong>{{ $user->full_address }}</strong>
									</div>
									<div class="mb-2">
										<x-icon name="building-hospital" />
										Emergency contact details:
										<strong>{{ $user->emergency_contact_details }}</strong>
									</div>
									<div class="mb-2">
										<x-icon name="glass" />
										Over 18:
										<strong>{{ $user->is_over_18 ? 'Yes' : 'No' }}</strong>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="mt-0 row row-cards">
						<div class="col-12">
							<div class="card">
								<div class="card-body">
									<div class="card-title">Additional info</div>
									<div class="mb-2">
										<x-icon name="camera" />
										Photograph permssion:
										<strong>{{ $user->has_photo_permission ? 'Yes' : 'No' }}</strong>
									</div>
									<div class="mb-2">
										<x-icon name="gift" />
										Gift aid subs:
										<strong>{{ $user->is_gift_aiding_subs ? 'Yes' : 'No' }}</strong>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-layout>
