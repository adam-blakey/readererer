@props(['term_dates', 'totals', 'page_name', 'page_subname'])

<x-layout :$page_name :$page_subname>
	<div class="container-xl">
		<x-card-row>
			<div class="col-12">
				<x-card>
					<div class="card-header">
						<h2 class="mb-0 card-heading">Your upcoming rehearsals and concerts</h2>
					</div>
					@if ($term_dates->isEmpty())
						<x-card-body>
							<p class="mb-0 text-muted">
								Nothing coming up. Once you belong to an ensemble with dates in an upcoming term, they will show here.
							</p>
						</x-card-body>
					@else
						<div class="table-responsive">
							<table class="table table-vcenter card-table">
								<thead>
									<tr>
										<th>Date</th>
										<th>Time</th>
										<th>Type</th>
										<th>Who's playing</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									@foreach ($term_dates as $term_date)
										<tr>
											<td>{{ $term_date->date_label }}</td>
											<td class="text-nowrap">{{ $term_date->time_label }}</td>
											<td>
												@if ($term_date->isConcert())
													<span class="badge bg-green text-green-fg">Concert @if ($term_date->concert_ensemble) ({{ $term_date->concert_ensemble->name }}) @endif</span>
												@else
													<span class="badge bg-gray text-muted">Rehearsal</span>
												@endif
											</td>
											<td>
												<x-playing.summary :totals="$totals[$term_date->id]" />
											</td>
											<td class="text-end">
												<x-a class="btn btn-sm" href="{{ route('playing.show', $term_date) }}">
													<x-icon name="users-group" />
													See who's playing
												</x-a>
											</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					@endif
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>
