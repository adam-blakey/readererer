@props(['members', 'term', 'term_dates', 'instrument_families', 'van_driver_ids', 'ensemble', 'sortby', 'sortdir' => 'asc', 'ended' => false, 'next_term' => null])

@push('scripts')
	<script src="/js/three-state-checkbox.js"></script>
@endpush

@php
	$assume_attending = config('app.readererer_assume_attending');
	$allow_change_to_unknown = config('app.readererer_allow_change_to_unknown');
	$repeating_headings = config('app.readererer_repeating_headings');

	$sort_options = array_filter([
	    'first_name' => __('First name'),
	    'last_name' => __('Last name'),
	    'instrument_family' => __('Instrument'),
	    'setup_group' => config('app.readererer_setup_groups') ? __('Setup group') : null,
	]);
	$sortby = array_key_exists($sortby, $sort_options) ? $sortby : 'first_name';

	switch ($sortby) {
	    case 'last_name':
	        $members = $members->sortBy(fn ($member) => [$member->last_name, $member->first_name]);
	        $sort_attribute = 'last_name_initial';
	        break;
	    case 'instrument_family':
	        $members = $members->sortBy(fn ($member) => [$instrument_families->get($member->pivot->instrument_family_id)?->name, $member->first_name]);
	        $sort_attribute = 'instrument_family';
	        break;
	    case 'setup_group':
	        $members = $members->sortBy(fn ($member) => [$member->setup_group?->week ?? PHP_INT_MAX, $member->first_name]);
	        $sort_attribute = 'setup_group';
	        break;
	    default:
	        $members = $members->sortBy(fn ($member) => [$member->first_name, $member->last_name]);
	        $sort_attribute = 'first_name_initial';
	        break;
	}

	if ($sortdir === 'desc') {
	    $members = $members->reverse();
	}

	$spans_multiple_years = $term->earliest_date->year != $term->latest_date->year;

	$can_take_registers = Gate::allows('viewAny', App\Models\RegisterEntry::class);
	$locked_ids = $term_dates->filter->isLockedFor(auth()->user())->pluck('id');
	$locked_by_reminders = $term_dates->contains(fn ($term_date) => $locked_ids->contains($term_date->id) && $term_date->isLockedByReminders() && $term_date->start_datetime->isFuture());

	$self = auth()->user()?->role !== App\Enums\UserRole::Ensemble ? $members->firstWhere('id', auth()->id()) : null;
	$own_view = $self && auth()->user()->setting('poll_own_view', false);
	$show_registers = $can_take_registers && $term_dates->isNotEmpty();

	// Dates away from the term's default location are marked with footnote symbols, doubled once they run out (*, †, … then **, ††, …).
	$footnote_symbols = ['*', '†', '‡', '§', '¶', '‖'];
	$other_locations = $term_dates->map->inferred_location->filter()->unique('id')
		->reject(fn ($location) => $location->is($term->location))->values();
	$location_symbols = $other_locations->mapWithKeys(fn ($location, $index) => [
		$location->id => str_repeat($footnote_symbols[$index % count($footnote_symbols)], intdiv($index, count($footnote_symbols)) + 1),
	]);
@endphp

<div class="card-header d-print-none">
	<h2 class="mb-0 card-title">{{ $ensemble->name }}: {{ $term->name }}</h2>
	<div class="ms-auto btn-list align-items-center">
		@unless ($own_view)
			<div aria-label="{{ __('Sort members by') }}" class="btn-group" role="group">
				@foreach ($sort_options as $option => $label)
					<x-a class="btn btn-sm {{ $sortby === $option ? 'active' : '' }}" href="{{ route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $term, 'sortby' => $option, 'sortdir' => $sortdir]) }}">{{ $label }}</x-a>
				@endforeach
			</div>
			<x-a class="btn btn-sm btn-icon" href="{{ route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $term, 'sortby' => $sortby, 'sortdir' => $sortdir === 'desc' ? 'asc' : 'desc']) }}" aria-label="{{ $sortdir === 'desc' ? __('Sort ascending') : __('Sort descending') }}" title="{{ $sortdir === 'desc' ? __('Sort ascending') : __('Sort descending') }}">
				<x-icon :name="$sortdir === 'desc' ? 'sort-descending' : 'sort-ascending'" />
			</x-a>
		@endunless
			@if ($self)
				<form action="{{ route('attendance.poll.view') }}" method="POST">
					@csrf
					@method('PUT')
					<input name="own" type="hidden" value="0">
					<label class="form-check form-switch mb-0">
						<input class="form-check-input" name="own" data-submit-on-change type="checkbox" value="1" @checked($own_view)>
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

@if ($term->location or $other_locations->isNotEmpty())
	<div class="card-body py-2 border-bottom">
		<div class="list-inline list-inline-dots mb-0">
			<span class="list-inline-item"><x-icon name="map-pin" /> {{ __('Locations') }}: <x-location-link :location="$term->location" /></span>
			@foreach ($other_locations as $location)
				<span class="list-inline-item"><strong>{{ $location_symbols[$location->id] }}</strong> <x-location-link :location="$location" /></span>
			@endforeach
		</div>
	</div>
@endif

@if ($ended)
	<div class="card-body">
		<div class="alert alert-info mb-0" role="alert">
			{{ __('This poll has ended.') }}
			@if ($next_term)
				<a href="{{ route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $next_term]) }}">{{ __('Go to the :term poll', ['term' => $next_term->name]) }}</a>
			@endif
		</div>
	</div>
@endif

@if ($locked_by_reminders)
	<div class="card-body">
		<div class="alert alert-info mb-0" role="alert">{{ __("Replies close when a date's reminders are sent. Ask an admin to change a closed reply.") }}</div>
	</div>
@endif

<div class="table-responsive">
	<form action="{{ route('attendance.poll.store', ['ensemble' => $ensemble, 'term' => $term]) }}" method="POST" data-dirty-check>
		@csrf
		@if ($errors->any())
			<div class="card-body">
				<x-forms.error-summary />
			</div>
		@endif
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
						$is_van_driver = $van_driver_ids->get($term_date->id) === $self->id;
						$attendance = $self->latestAttendanceFor($term_date, $ensemble);
					@endphp
					<tr>
						<td class="{{ $term_date->concert_ensembles->contains($ensemble) ? 'bg-primary-subtle' : '' }}">
							{{ $term_date->schedule_label }}
							@if ($symbol = $location_symbols[$term_date->inferred_location?->id] ?? null)
								<strong title="{{ $term_date->location_label }}">{{ $symbol }}</strong>
							@endif
							@if ($term_date->is_concert)
								<span class="ms-1 badge bg-primary-lt">{{ __('Concert') }}</span>
							@endif
							@if ($setup_group and config('app.readererer_setup_groups'))
								<span class="ms-1 badge rounded-pill bg-{{ $setup_group->color->cssClass() }} text-{{ $setup_group->color->cssClass() }}-fg">{{ __('Setup group :week', ['week' => $setup_group->week]) }}</span>
							@endif
							@if ($is_van_driver)
								<span class="ms-1 badge rounded-pill bg-secondary text-secondary-fg"><x-icon name="truck" /> {{ __('Van driver') }}</span>
							@endif
						</td>
						<td class="w-1 text-center {{ $term_date->concert_ensembles->contains($ensemble) ? 'bg-primary-subtle' : '' }}">
							<x-forms.input-three-state-checkbox :$allow_change_to_unknown :$assume_attending :member_id="$self->id" :status="$attendance?->status ?? App\Enums\AttendanceStatus::Unknown" :dep_name="$attendance?->dep_name" :term_date_id="$term_date->id" :$setup_group :$is_van_driver :locked="$locked_ids->contains($term_date->id)" />
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
						$sort_value = match ($sort_attribute) {
						    'instrument_family' => $instrument_families->get($member->pivot->instrument_family_id)?->name,
						    'setup_group' => $member->setup_group?->label ?? __('No setup group'),
						    default => $member->$sort_attribute,
						};
					@endphp

					@if ($repeating_headings and $sort_value != $prev_sort_value or $loop->first)
						<thead>
                            <x-attendances.heading :$term_dates :show_year="$spans_multiple_years" :ensemble="$ensemble" :$location_symbols />
						</thead>
					@endif
					@if ($loop->first)
						<x-attendances.totals-row :$ensemble :$members :$term_dates />
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
								@php
									$last_updated = $member->attendances->where('ensemble_id', $ensemble->id)->max('created_at');
								@endphp
								<a class="btn btn-sm btn-ghost-secondary ms-auto d-print-none" href="{{ route('attendance.poll.history', ['ensemble' => $ensemble, 'term' => $term, 'user' => $member]) }}" aria-label="{{ __('View edit history for :name', ['name' => $member->name]) }}" title="{{ __('View edit history') }}" data-bs-toggle="tooltip">
									<x-icon name="history" />
									{{ $last_updated?->diffForHumans(short: true) ?? __('Never') }}
								</a>
							</div>
						</td>
						@foreach ($term_dates as $term_date)
                            <td class="w-1 {{ $term_date->concert_ensembles->contains($ensemble) ? 'bg-primary-subtle' : '' }}">
								@php
									$attendance = $member->latestAttendanceFor($term_date, $ensemble);
									$attendance_value = $attendance?->status ?? App\Enums\AttendanceStatus::Unknown;

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
								<x-forms.input-three-state-checkbox :$allow_change_to_unknown :$assume_attending :member_id="$member->id" :status="$attendance_value" :dep_name="$attendance?->dep_name" :term_date_id="$term_date->id" :$setup_group :$is_van_driver :locked="$locked_ids->contains($term_date->id)" />
							</td>
						@endforeach
					</tr>

					@php
						$prev_sort_value = $sort_value;
					@endphp
				@endforeach
				<x-attendances.totals-row :$ensemble :$members :$term_dates />
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
