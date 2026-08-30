@props(['term_dates', 'ensembles'])

@use('App\Enums\RegisterStatus')

<div class="table-responsive">
	<table class="table table-vcenter card-table">
		<thead>
			<tr>
				<th>{{ __('Date') }}</th>
				<th>{{ __('Time') }}</th>
				<th>{{ __('Type') }}</th>
				<th>{{ __('Term') }}</th>
				<th>{{ __('Registers') }}</th>
			</tr>
		</thead>
		<tbody>
			@forelse ($term_dates as $term_date)
				<tr>
					<td>{{ $term_date->date_label }}</td>
					<td class="text-nowrap">{{ $term_date->time_label }}</td>
					<td>
						@if ($term_date->concert_ensemble_id)
							<span class="badge bg-green text-green-fg">{{ __('Concert') }} @if ($term_date->concert_ensemble) ({{ $term_date->concert_ensemble->name }}) @endif</span>
						@else
							<span class="badge bg-gray text-muted">{{ __('Rehearsal') }}</span>
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
											<span class="ms-1 badge bg-green-lt">{{ __(':count present', ['count' => $present]) }}</span>
										@else
											<span class="ms-1 badge bg-secondary-lt">{{ __('Not taken') }}</span>
										@endif
									</x-a>
								@endforeach
							</div>
						@endif
					</td>
				</tr>
			@empty
				<tr>
					<td colspan="5">{{ __('No rehearsals or concerts have been scheduled yet.') }}</td>
				</tr>
			@endforelse
		</tbody>
	</table>
</div>
