@props(['members', 'term', 'ensemble', 'sortby'])

@push('scripts')
	<script src="/js/three-state-checkbox.js"></script>
@endpush

@php
	$assume_attending = config('app.readererer_assume_attending');
	$allow_change_to_unknown = config('app.readererer_allow_change_to_unknown');
	$repeating_headings = config('app.readererer_repeating_headings');

	switch ($sortby) {
	    case 'first_name':
	        $members = $members->sortBy('first_name');
	        $sort_attribute = 'first_name_initial';
	        break;
	    case 'last_name':
	        $members = $members->sortBy('last_name');
	        $sort_attribute = 'last_name_initial';
	        break;
	    case 'instrument_family':
	        $members = $members->sortBy(function ($member) use ($ensemble) {
	            return App\Models\InstrumentFamily::find($member->ensembles->where('id', $ensemble->id)->first()->pivot->instrument_family_id)->name;
	        });
	        $sort_attribute = 'instrument_family';
	        break;
	    default:
	        $members = $members->sortBy('first_name');
	        $sort_attribute = 'first_name_initial';
	        break;
	}

	$term_dates = $term->term_dates->sortBy('start_datetime');

	$spans_multiple_years = $term->earliest_date->year != $term->latest_date->year;
@endphp

<div class="table-responsive">
	<form action="{{ route('attendance.poll-store', ['ensemble' => $ensemble, 'term' => $term]) }}" method="POST">
		@csrf
		<table class="table table-vcenter card-table text-nowrap datatable">
			<tbody>
				@php
					$prev_sort_value = null;
				@endphp
				@foreach ($members as $member)
					@php
						$sort_value = $member->$sort_attribute;
					@endphp

					@if ($repeating_headings and $sort_value != $prev_sort_value or $loop->first)
						<thead>
                            <x-attendances.heading :$term_dates :show_year="$spans_multiple_years" :ensemble="$ensemble" />
						</thead>
					@endif
					@if ($repeating_headings and $sort_value != $prev_sort_value)
						<tr>
							<td>
								@if ($sort_attribute == 'instrument_family')
									{{ App\Models\InstrumentFamily::find($member->ensembles->where('id', $ensemble->id)->first()->pivot->instrument_family_id)->name }}
								@else
									{{ $member->$sort_attribute }}
								@endif
							</td>
							@foreach ($term_dates as $term_date)
                                <td class="w-1 {{ ((int)($term_date->concert_ensemble_id) === (int)($ensemble->id)) ? 'bg-primary-subtle' : '' }}"></td>
							@endforeach
						</tr>
					@endif

					<tr>
						<td>
							<x-user-entry :add_route="false" :secondary_info="App\Models\InstrumentFamily::find($member->ensembles->where('id', $ensemble->id)->first()->pivot->instrument_family_id)->name" :user="$member" />
						</td>
						@foreach ($term_dates as $term_date)
                            <td class="w-1 {{ ((int)($term_date->concert_ensemble_id) === (int)($ensemble->id)) ? 'bg-primary-subtle' : '' }}">
								@php
									$attendance = $member->attendances->where('term_date_id', $term_date->id)->sortByDesc('created_at')->first();
									$attendance_value = $attendance->status ?? App\Enums\AttendanceStatus::Unknown;
								@endphp
								<x-forms.input-three-state-checkbox :$allow_change_to_unknown :$assume_attending :member_id="$member->id" :status="$attendance_value" :term_date_id="$term_date->id" />
							</td>
						@endforeach
					</tr>

					@php
						$prev_sort_value = $sort_value;
					@endphp
				@endforeach
				<tr>
					<td class="align-right" colspan="{{ $term->term_dates->count() + 1 }}">
						<button class="btn btn-primary" type="submit">Save</button>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
</div>
