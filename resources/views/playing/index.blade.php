@props(['term_dates', 'ensembles', 'page_name', 'page_subname'])

<x-layout :$page_name :$page_subname>
	<div class="container-xl">
		@if ($term_dates->isEmpty())
			<x-card-row>
				<div class="col-12">
					<x-card>
						<x-card-body>
							<p class="mb-0 text-muted">
								Nothing coming up. Once you belong to an ensemble with dates in an upcoming term, they will show here.
							</p>
						</x-card-body>
					</x-card>
				</div>
			</x-card-row>
		@else
			<x-card-row>
				@foreach ($term_dates as $term_date)
					<div class="col-md-6">
						<x-card>
							<div class="card-header">
								<div>
									<h3 class="mb-1 card-title">{{ $term_date->date_label }}</h3>
									<div class="text-muted">
										{{ $term_date->time_label }}
										@if ($term_date->isConcert())
											<span class="badge bg-green text-green-fg ms-1">Concert @if ($term_date->concert_ensemble) ({{ $term_date->concert_ensemble->name }}) @endif</span>
										@else
											<span class="badge bg-gray text-muted ms-1">Rehearsal</span>
										@endif
									</div>
								</div>
							</div>
							<x-card-body>
								@php $playing_ensembles = $ensembles[$term_date->id]; @endphp
								@if ($playing_ensembles->isEmpty())
									<p class="mb-0 text-muted">No ensembles have members yet.</p>
								@else
									<div class="card-title">You're playing with</div>
									@foreach ($playing_ensembles as $entry)
										<x-playing.ensemble-entry :ensemble="$entry['ensemble']" :totals="$entry['totals']" :is_yours="$entry['is_yours']" />
									@endforeach
								@endif
							</x-card-body>
						</x-card>
					</div>
				@endforeach
			</x-card-row>
		@endif
	</div>
</x-layout>
