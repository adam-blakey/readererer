@props(['members', 'term', 'term_dates', 'instrument_families', 'van_driver_ids', 'ensemble', 'sortby'])

@push('scripts')
	<script src="/js/three-state-checkbox.js"></script>
@endpush

@php
	$assume_attending = config('app.readererer_assume_attending');
	$allow_change_to_unknown = config('app.readererer_allow_change_to_unknown');
	$repeating_headings = config('app.readererer_repeating_headings');

	switch ($sortby) {
	    case 'last_name':
	        $members = $members->sortBy('last_name');
	        $sort_attribute = 'last_name_initial';
	        break;
	    case 'instrument_family':
	        $members = $members->sortBy(fn ($member) => $instrument_families->get($member->pivot->instrument_family_id)?->name);
	        $sort_attribute = 'instrument_family';
	        break;
	    default:
	        $members = $members->sortBy('first_name');
	        $sort_attribute = 'first_name_initial';
	        break;
	}

	$spans_multiple_years = $term->earliest_date->year != $term->latest_date->year;

	$can_take_registers = Gate::allows('viewAny', App\Models\RegisterEntry::class);
@endphp

@if ($can_take_registers and $term_dates->isNotEmpty())
	<div class="card-header d-print-none">
		<h2 class="mb-0 card-title">{{ $ensemble->name }}: {{ $term->name }}</h2>
		<div class="ms-auto btn-list">
			<div class="dropdown">
				<button aria-expanded="false" class="btn btn-sm dropdown-toggle" data-bs-toggle="dropdown" type="button">
					<x-icon name="list-check" />
					{{ __('Register') }}
				</button>
				<div class="dropdown-menu dropdown-menu-end">
					@foreach ($term_dates as $term_date)
						<a class="dropdown-item" href="{{ route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $term_date]) }}">
							{{ $term_date->date_label }}
							@if ($term_date->concert_ensemble_id)
								<span class="ms-1 badge bg-green-lt">{{ __('Concert') }}</span>
							@endif
						</a>
					@endforeach
				</div>
			</div>
		</div>
	</div>
@endif

<div class="table-responsive">
	<form action="{{ route('attendance.poll.store', ['ensemble' => $ensemble, 'term' => $term]) }}" method="POST" data-dirty-check>
		@csrf
		<table class="table table-vcenter card-table text-nowrap datatable">
			<tbody>
				@php
					$prev_sort_value = null;
				@endphp
				@foreach ($members as $member)
					@php
						$sort_value = ($sort_attribute == 'instrument_family')
							? $instrument_families->get($member->pivot->instrument_family_id)?->name
							: $member->$sort_attribute;
					@endphp

					@if ($repeating_headings and $sort_value != $prev_sort_value or $loop->first)
						<thead>
                            <x-attendances.heading :$term_dates :show_year="$spans_multiple_years" :ensemble="$ensemble" />
						</thead>
					@endif
					@if ($repeating_headings and $sort_value != $prev_sort_value)
						<tr>
							<td>{{ $sort_value }}</td>
							@foreach ($term_dates as $term_date)
                                <td class="w-1 {{ ((int)($term_date->concert_ensemble_id) === (int)($ensemble->id)) ? 'bg-primary-subtle' : '' }}"></td>
							@endforeach
						</tr>
					@endif

					<tr>
						<td>
							<x-user-entry :add_route="false" :secondary_info="$instrument_families->get($member->pivot->instrument_family_id)?->name ?? ''" :user="$member" show_setup_group="true" />
						</td>
						@foreach ($term_dates as $term_date)
                            <td class="w-1 {{ ((int)($term_date->concert_ensemble_id) === (int)($ensemble->id)) ? 'bg-primary-subtle' : '' }}">
								@php
									$attendance_value = $member->latestAttendanceFor($term_date, $ensemble)?->status ?? App\Enums\AttendanceStatus::Unknown;

                                    if ($term_date->setup_group != null && $member->setup_group != null)
                                    {
                                        $setup_group =  ($term_date->setup_group->id == $member->setup_group->id) ? $member->setup_group : null;
                                    }
                                    else
                                    {
                                        $setup_group = null;
                                    }

                                    $is_van_driver = $van_driver_ids->get($term_date->id) === $member->id;
								@endphp
								<x-forms.input-three-state-checkbox :$allow_change_to_unknown :$assume_attending :member_id="$member->id" :status="$attendance_value" :term_date_id="$term_date->id" :$setup_group :$is_van_driver />
							</td>
						@endforeach
					</tr>

					@php
						$prev_sort_value = $sort_value;
					@endphp
				@endforeach
				<tr>
					<td class="align-right" colspan="{{ $term_dates->count() + 1 }}">
						<button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
</div>
