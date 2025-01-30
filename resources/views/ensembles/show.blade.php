@props(['ensemble', 'page_name'])

@php
	$terms = App\Models\Term::all()->sortBy('earliest_date');
@endphp

<x-layout :$page_name :show_page_header="0">
	<div class="page-header">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-auto">
					<span class="rounded avatar avatar-lg" style="background-image: url({{ $ensemble->image }})"></span>
				</div>
				<div class="col">
					<h1 class="my-0 font-bold">{{ $ensemble->name }}</h1>
					<span class="badge bg-blue text-blue-fg">You're a member!</span>
				</div>
				<div class="col-auto ms-auto">
					<div class="btn-list">
						<a aria-label="Button" class="btn" href="{{ route('ensembles.edit', ['ensemble' => $ensemble]) }}">
							<!-- Download SVG icon from http://tabler-icons.io/i/message -->
							<svg class="icon icon-tabler icons-tabler-outline icon-tabler-pencil" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
								<path d="M0 0h24v24H0z" fill="none" stroke="none" />
								<path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
								<path d="M13.5 6.5l4 4" />
							</svg>
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
							<h2 class="mb-0 card-heading">Next rehearsal or concert</h2>
						</div>
						<div class="card-body">

						</div>
					</div>
					<div class="mb-3 card">
						<div class="card-header">
							<h2 class="mb-0 card-heading">Current pieces</h2>
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
									<div class="card-title">Basic info</div>
									<div class="mb-2">
										<x-icon name="user" />
										Admins:
										@foreach ($ensemble->admins as $admin)
											<a href="#">{{ $admin->name }}</a>{{ $loop->last ? '' : ',' }}
										@endforeach
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
