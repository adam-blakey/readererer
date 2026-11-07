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
	$can_view_history = Gate::allows('viewAny', App\Models\Attendance::class);
	$locked_ids = $term_dates->filter->isLockedFor(auth()->user())->pluck('id');

	$self = auth()->user()?->role !== App\Enums\UserRole::Ensemble ? $members->firstWhere('id', auth()->id()) : null;
	$own_view = $self && auth()->user()->setting('poll_own_view', false);
	$show_registers = $can_take_registers && $term_dates->isNotEmpty();
@endphp

@if ($show_registers or $self)
	<div class="card-header d-print-none">
		<h2 class="mb-0 card-title">{{ $ensemble->name }}: {{ $term->name }}</h2>
		<div class="ms-auto btn-list">
			@if ($self)
				<form action="{{ route('attendance.poll.view') }}" method="POST">
					@csrf
					@method('PUT')
					<input name="own" type="hidden" value="0">
					<label class="form-check form-switch mb-0">
						<input class="form-check-input" name="own" onchange="this.form.submit()" type="checkbox" value="1" @checked($own_view)>
						<span class="form-check-label">{{ __('Only show me') }}</span>
					</label>
				</form>
			@endif
			@if ($show_registers)
				<div class="dropdown">
					<button aria-expanded="false" class="btn btn-sm dropdown-toggle" data-bs-toggle="dropdown" type="button">
						<x-icon name="list-check" />
						{{ __('Register') }}
					</button>
					<div class="dropdown-menu dropdown-menu-end">
						@foreach ($term_dates as $term_date)
							<a class="dropdown-item" href="{{ route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $term_date]) }}">
								{{ $term_date->date_label }}
								@if ($term_date->is_concert)
									<span class="ms-1 badge bg-primary-lt">{{ __('Concert') }}</span>
								@endif
							</a>
						@endforeach
					</div>
				</div>
			@endif
		</div>
	</div>
@endif

@if ($locked_ids->isNotEmpty())
	<div class="card-body">
		<div class="alert alert-info mb-0" role="alert">{{ trans_choice('Replies close :count hour before each date starts. Ask an admin to change a closed reply.|Replies close :count hours before each date starts. Ask an admin to change a closed reply.', config('app.readererer_reminder_hours')) }}</div>
	</div>
@endif

<div class="table-responsive">
	<form action="{{ route('attendance.poll.store', ['ensemble' => $ensemble, 'term' => $term]) }}" method="POST" data-dirty-check>
		@csrf
		@if ($own_view)
		<table class="table table-vcenter card-table text-nowrap">
			<thead>
				<tr>
					<th>{{ __('Date') }}</th>
					<th class="w-1 text-center">{{ $self->name }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($term_dates as $term_date)
					@php
						$setup_group = ($term_date->setup_group && $self->setup_group && $term_date->setup_group->id == $self->setup_group->id) ? $self->setup_group : null;
					@endphp
					<tr>
						<td class="{{ $term_date->concert_ensembles->contains($ensemble) ? 'bg-primary-subtle' : '' }}">
							{{ $term_date->schedule_label }}
							@if ($term_date->is_concert)
								<span class="ms-1 badge bg-primary-lt">{{ __('Concert') }}</span>
							@endif
						</td>
						<td class="w-1 text-center {{ $term_date->concert_ensembles->contains($ensemble) ? 'bg-primary-subtle' : '' }}">
							<x-forms.input-three-state-checkbox :$allow_change_to_unknown :$assume_attending :member_id="$self->id" :status="$self->latestAttendanceFor($term_date, $ensemble)?->status ?? App\Enums\AttendanceStatus::Unknown" :term_date_id="$term_date->id" :$setup_group :is_van_driver="$van_driver_ids->get($term_date->id) === $self->id" :locked="$locked_ids->contains($term_date->id)" />
						</td>
					</tr>
				@endforeach
				<tr>
					<td class="align-right" colspan="2">
						<button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
					</td>
				</tr>
			</tbody>
		</table>
		@else
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
                                <td class="w-1 {{ $term_date->concert_ensembles->contains($ensemble) ? 'bg-primary-subtle' : '' }}"></td>
							@endforeach
						</tr>
					@endif

					<tr>
						<td>
							<div class="d-flex align-items-center">
								<x-user-entry :add_route="false" :secondary_info="$instrument_families->get($member->pivot->instrument_family_id)?->name ?? ''" :user="$member" show_setup_group="true" />
								@if ($can_view_history)
									<a class="btn btn-sm btn-ghost-secondary btn-icon ms-auto d-print-none" href="{{ route('attendance.poll.history', ['ensemble' => $ensemble, 'term' => $term, 'user' => $member]) }}" aria-label="{{ __('View edit history for :name', ['name' => $member->name]) }}" title="{{ __('View edit history') }}" data-bs-toggle="tooltip">
										<x-icon name="history" />
									</a>
								@endif
							</div>
						</td>
						@foreach ($term_dates as $term_date)
                            <td class="w-1 {{ $term_date->concert_ensembles->contains($ensemble) ? 'bg-primary-subtle' : '' }}">
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
								<x-forms.input-three-state-checkbox :$allow_change_to_unknown :$assume_attending :member_id="$member->id" :status="$attendance_value" :term_date_id="$term_date->id" :$setup_group :is_van_driver="$van_driver_ids->get($term_date->id) === $self->id" :locked="$locked_ids->contains($term_date->id)" />
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
		@endif
	</form>
</div>
