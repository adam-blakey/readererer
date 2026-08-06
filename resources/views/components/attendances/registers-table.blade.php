@props(['term_dates', 'ensembles'])

@use('App\Enums\RegisterStatus')

<div class="table-responsive">
	<table class="table table-vcenter card-table">
		<thead>
			<tr>
				<th>Date</th>
				<th>Time</th>
				<th>Type</th>
				<th>Term</th>
				<th>Registers</th>
			</tr>
		</thead>
		<tbody>
			@forelse ($term_dates as $term_date)
				<tr>
					<td>{{ $term_date->date_label }}</td>
					<td class="text-nowrap">{{ $term_date->time_label }}</td>
					<td>
						@if ($term_date->concert_ensemble_id)
							<span class="badge bg-green text-green-fg">Concert @if ($term_date->concert_ensemble) ({{ $term_date->concert_ensemble->name }}) @endif</span>
						@else
							<span class="badge bg-gray text-muted">Rehearsal</span>
						@endif
					</td>
					<td>{{ $term_date->term?->name }}</td>
					<td>
						@php
							$applicable_ensembles = $ensembles->filter(fn ($ensemble) => $term_date->appliesToEnsemble($ensemble));
						@endphp
						@if ($applicable_ensembles->isEmpty())
							<span class="text-muted">—</span>
						@else
							<div class="btn-list">
								@foreach ($applicable_ensembles as $ensemble)
									@php
										$entries = $term_date->register_entries->where('ensemble_id', $ensemble->id);
										$present = $entries->whereIn('status', [RegisterStatus::Present, RegisterStatus::Late])->count();
									@endphp
									<x-a class="btn btn-sm" href="{{ route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $term_date]) }}">
										<x-icon name="list-check" />
										{{ $ensemble->name }}
										@if ($entries->isNotEmpty())
											<span class="ms-1 badge bg-green-lt">{{ $present }} present</span>
										@else
											<span class="ms-1 badge bg-secondary-lt">Not taken</span>
										@endif
									</x-a>
								@endforeach
							</div>
						@endif
					</td>
				</tr>
			@empty
				<tr>
					<td colspan="5">No rehearsals or concerts have been scheduled yet.</td>
				</tr>
			@endforelse
		</tbody>
	</table>
</div>
