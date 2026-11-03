@props(['page_name', 'ensembles' => collect(), 'setupGroup' => null, 'nextRehearsal' => null, 'nextConcerts' => collect(), 'nextSetup' => null, 'nextVanDrive' => null, 'drivesTheVan' => false])

<x-layout>
	<div class="container-xl">
		<x-card-row>
			@if($ensembles->isEmpty())
				<div class="col-12">
					<x-card>
						<div class="card-header">
							<h2 class="mb-0 card-heading">{{ __('Your ensembles') }}</h2>
						</div>
						<x-card-body>
							<p>{{ __('You are not currently a member of any ensembles.') }}</p>
							<p class="text-muted mb-0">{{ __('Your rehearsals, concerts, setup duties and van drives will appear here once an administrator adds you to an ensemble.') }}</p>
						</x-card-body>
					</x-card>
				</div>
			@else
				<div class="col-md-8">
					<x-card>
						<div class="card-header">
							<h2 class="mb-0 card-heading">{{ __('Your ensembles') }}</h2>
						</div>
						<x-card-body>
							<div class="row row-cards">
								@foreach($ensembles as $ensemble)
									<div class="col-12 col-sm-6 col-md-4">
										<x-a route="ensembles.show" :$ensemble class="card text-center">
											<div class="card-body p-3">
												<span class="avatar avatar-xl mb-2" style="background-image: url({{ $ensemble->image_url }})"></span>
												<div class="fw-medium">{{ $ensemble->name }}</div>
											</div>
										</x-a>
									</div>
								@endforeach
							</div>
						</x-card-body>
					</x-card>

					<x-card class="mt-3">
						<div class="card-header">
							<h2 class="mb-0 card-heading">{{ __('Upcoming rehearsals and concerts') }}</h2>
						</div>
						<x-card-body>
							<div class="card-title">{{ __('Next rehearsal') }}</div>
							<x-rehearsal-entry :ensembles="$ensembles" :term_date="$nextRehearsal" />
						</x-card-body>
						<x-card-body>
							<div class="card-title">{{ __('Your next concerts') }}</div>
							@if($nextConcerts->count() === 0)
								<x-rehearsal-entry :term_date="null" />
							@else
								@foreach($nextConcerts as $concert)
									<div class="d-flex align-items-center justify-content-between py-1">
										<div>
											<x-rehearsal-entry :ensembles="$ensembles" :term_date="$concert" />
										</div>
										<div class="ms-2 small text-muted">{{ $concert->concert_ensemble_names }}</div>
									</div>
								@endforeach
							@endif
						</x-card-body>
					</x-card>
				</div>
				<div class="col-md-4">
					<x-card>
						<div class="card-header">
							<h2 class="mb-0 card-heading">{{ __('Your setup group') }}</h2>
						</div>
						<x-card-body>
							@if($setupGroup)
								<div class="d-flex align-items-center">
									<x-setup-group-badge :setup_group="$setupGroup" />
									<span class="ms-2">{{ $setupGroup->name }}</span>
								</div>
							@else
								<p class="text-muted mb-0">{{ __('You are not assigned to a setup group.') }}</p>
							@endif
						</x-card-body>
						@if($setupGroup)
							<x-card-body>
								<div class="card-title">{{ __('Next time your group is setting up') }}</div>
								<x-rehearsal-entry :term_date="$nextSetup" />
							</x-card-body>
						@endif
					</x-card>

					@if($drivesTheVan)
						<x-card class="mt-3">
							<div class="card-header">
								<h2 class="mb-0 card-heading">{{ __("Next time you're driving the van") }}</h2>
							</div>
							<x-card-body>
								<x-rehearsal-entry :term_date="$nextVanDrive" />
							</x-card-body>
						</x-card>
					@endif
				</div>
			@endif
		</x-card-row>
	</div>
</x-layout>
