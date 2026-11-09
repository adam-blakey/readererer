@props(['page_name', 'ensembles' => collect(), 'instrumentFamilies' => null, 'setupGroup' => null, 'nextRehearsal' => null, 'nextConcerts' => collect(), 'nextSetup' => null, 'nextVanDrive' => null, 'drivesTheVan' => false, 'attendanceUpdates' => null, 'notifications' => null])

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
				@php($show_duties = config('app.readererer_setup_groups') || $drivesTheVan)
				<div @class(['col-md-8' => $show_duties, 'col-12' => ! $show_duties])>
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
												@if($instrumentFamilies !== null)
													<div class="text-muted small">{{ $instrumentFamilies[$ensemble->pivot->instrument_family_id]->name ?? __('No part') }}</div>
													<div class="text-muted small">{{ $ensemble->pivot->seat_row !== null && $ensemble->pivot->seat_column !== null ? __('Row :row, seat :seat', ['row' => $ensemble->pivot->seat_row, 'seat' => $ensemble->pivot->seat_column]) : __('No seat') }}</div>
												@endif
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
				@if($show_duties)
				<div class="col-md-4">
					@if(config('app.readererer_setup_groups'))
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
					@endif

					@if($drivesTheVan)
						<x-card :class="config('app.readererer_setup_groups') ? 'mt-3' : ''">
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
			@endif

			@if($attendanceUpdates)
				<div class="col-12">
					<x-card>
						<div class="card-header">
							<h2 class="mb-0 card-heading">{{ __('Attendance updates') }}</h2>
							<div class="card-actions">
								<a href="{{ route('attendance.index') }}">{{ __('View all') }}</a>
							</div>
						</div>
						<x-attendances.table :attendances="$attendanceUpdates" />
					</x-card>
				</div>
			@endif

			@if($notifications)
				<div class="col-12">
					<x-card>
						<div class="card-header">
							<h2 class="mb-0 card-heading">{{ __('Notifications') }}</h2>
							<div class="card-actions">
								<a href="{{ route('notifications.index') }}">{{ __('View all') }}</a>
							</div>
						</div>
						<x-notifications.table :logs="$notifications" />
					</x-card>
				</div>
			@endif
		</x-card-row>
	</div>
</x-layout>
