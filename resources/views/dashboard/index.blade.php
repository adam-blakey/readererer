@props(['page_name', 'ensembles' => collect(), 'setupGroup' => null, 'nextRehearsal' => null, 'nextConcerts' => collect(), 'nextVanDrive' => null])

<x-layout :$page_name page_subname="Dashboard">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-8">
				<x-card>
					<div class="card-header">
						<h2 class="mb-0 card-heading">Your ensembles</h2>
					</div>
					<x-card-body>
						@if($ensembles->count() === 0)
							<p>You are not currently a member of any ensembles.</p>
						@else
							<div class="row row-cards">
								@foreach($ensembles as $ensemble)
									<div class="col-12 col-sm-6 col-md-4">
										<x-a route="ensembles.show" :$ensemble class="card text-center">
											<div class="card-body p-3">
												<span class="avatar avatar-xl mb-2" style="background-image: url({{ $ensemble->image }})"></span>
												<div class="fw-medium">{{ $ensemble->name }}</div>
											</div>
										</x-a>
									</div>
								@endforeach
							</div>
						@endif
					</x-card-body>
				</x-card>

				<x-card class="mt-3">
					<div class="card-header">
						<h2 class="mb-0 card-heading">Upcoming rehearsals and concerts</h2>
					</div>
					<x-card-body>
						<div class="card-title">Next rehearsal</div>
						<x-rehearsal-entry :term_date="$nextRehearsal" />
					</x-card-body>
					<x-card-body>
						<div class="card-title">Your next concerts</div>
						@if($nextConcerts->count() === 0)
                            <div class="d-none d-xl-block ps-2">
                                <div class="text-muted">Nothing found.</div>
                            </div>
						@else
							@foreach($nextConcerts as $concert)
								<div class="d-flex align-items-center justify-content-between py-1">
									<div>
										<x-rehearsal-entry :term_date="$concert" />
									</div>
									<div class="ms-2 small text-muted">{{ optional($concert->concert_ensemble)->name }}</div>
								</div>
							@endforeach
						@endif
					</x-card-body>
				</x-card>
			</div>
			<div class="col-md-4">
				<x-card>
					<div class="card-header">
						<h2 class="mb-0 card-heading">Your setup group</h2>
					</div>
					<x-card-body>
						@if($setupGroup)
							<div class="d-flex align-items-center">
								<x-setup-group-badge :setup_group="$setupGroup" />
								<span class="ms-2">{{ $setupGroup->name }}</span>
							</div>
						@else
							<p class="text-muted">You are not assigned to a setup group.</p>
						@endif
					</x-card-body>
				</x-card>

				<x-card class="mt-3">
					<div class="card-header">
						<h2 class="mb-0 card-heading">Next time you're driving the van</h2>
					</div>
					<x-card-body>
						@if($nextVanDrive)
							<x-rehearsal-entry :term_date="$nextVanDrive" />
						@else
							<div class="text-muted">Nothing found.</div>
						@endif
					</x-card-body>
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>
