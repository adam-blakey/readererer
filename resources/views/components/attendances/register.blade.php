@props(['ensemble', 'term_date', 'members', 'entries', 'polled_statuses', 'instrument_families', 'sortby'])

@use('App\Enums\AttendanceStatus')
@use('App\Enums\RegisterStatus')

@push('scripts')
	<script src="/js/attendance-register.js"></script>
@endpush

@php
	$totals = register_status_totals($entries->only($members->pluck('id')->all()), $members->count());

	$sort_options = [
	    'first_name' => 'First name',
	    'last_name' => 'Last name',
	    'instrument_family' => 'Instrument',
	];

	// What the poll answer implies for the register, used by "Fill from poll".
	$register_status_from_poll = [
	    AttendanceStatus::Attending->value => RegisterStatus::Present,
	    AttendanceStatus::NotAttending->value => RegisterStatus::Absent,
	];
@endphp

<div class="card-header d-print-none">
	<div>
		<h2 class="mb-1 card-title">{{ $term_date->name }}</h2>
		<div class="list-inline list-inline-dots mb-0 text-secondary">
			<span class="list-inline-item">
				@if ($term_date->concert_ensemble_id)
					<span class="badge bg-green text-green-fg">Concert</span>
				@else
					<span class="badge bg-gray text-muted">Rehearsal</span>
				@endif
			</span>
			<span class="list-inline-item">{{ $ensemble->name }}</span>
			<span class="list-inline-item">{{ $term_date->term->name }}</span>
			@if ($term_date->setup_group != null)
				<span class="list-inline-item"><x-setup-group-badge :setup_group="$term_date->setup_group" /></span>
			@endif
		</div>
	</div>
	<div class="ms-auto btn-list">
		@foreach ($sort_options as $option => $label)
			<x-a class="btn btn-sm {{ $sortby === $option ? 'active' : '' }}" href="{{ route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $term_date, 'sortby' => $option]) }}">{{ $label }}</x-a>
		@endforeach
	</div>
</div>

<div class="card-body border-bottom py-3">
	<div class="row g-2 align-items-center">
		<div class="col">
			<div class="btn-list">
				@foreach (RegisterStatus::cases() as $status)
					<span class="badge bg-{{ $status->color() }}-lt">{{ $status->label() }}: {{ $totals[$status->key()] }}</span>
				@endforeach
			</div>
		</div>
		<div class="col-auto d-print-none">
			<div class="btn-list">
				<button class="btn btn-sm" type="button" onclick="fillRegisterFromPoll()">
					<x-icon name="clipboard-check" />
					Fill from poll
				</button>
				<button class="btn btn-sm" type="button" onclick="setWholeRegister({{ RegisterStatus::Present->value }})">
					<x-icon name="users-group" />
					All present
				</button>
				<button class="btn btn-sm" type="button" onclick="setWholeRegister({{ RegisterStatus::Unmarked->value }})">
					<x-icon name="eraser" />
					Clear
				</button>
			</div>
		</div>
	</div>
</div>

<form action="{{ route('attendance.register.store', ['ensemble' => $ensemble, 'termDate' => $term_date, 'sortby' => $sortby]) }}" method="POST">
	@csrf
	<div class="table-responsive">
		<table class="table table-vcenter card-table" id="attendance-register">
			<thead>
				<tr>
					<th>Member</th>
					<th class="w-1 text-nowrap">Said on the poll</th>
					<th class="w-1 text-center">Register</th>
					<th>Notes</th>
				</tr>
			</thead>
			<tbody>
				@forelse ($members as $member)
					@php
						$entry = $entries->get($member->id);
						$status = $entry?->status ?? RegisterStatus::Unmarked;
						$polled = $polled_statuses->get($member->id) ?? AttendanceStatus::Unknown;
						$polled_register_status = $register_status_from_poll[$polled->value] ?? RegisterStatus::Unmarked;
						$instrument_family = $instrument_families->get($member->pivot->instrument_family_id);
					@endphp
					<tr data-poll-status="{{ $polled_register_status->value }}">
						<td>
							<x-user-entry :add_route="false" :secondary_info="$instrument_family?->name ?? ''" :user="$member" show_setup_group="true" />
						</td>
						<td>
							@switch ($polled)
								@case (AttendanceStatus::Attending)
									<span class="badge bg-green-lt">Attending</span>
									@break
								@case (AttendanceStatus::NotAttending)
									<span class="badge bg-red-lt">Not attending</span>
									@break
								@default
									<span class="badge bg-secondary-lt">No answer</span>
							@endswitch
						</td>
						<td>
							<div aria-label="Register status for {{ $member->name }}" class="btn-group text-nowrap" role="group">
								@foreach ([RegisterStatus::Unmarked, ...RegisterStatus::choices()] as $choice)
									@php
										$input_id = 'status-'.$member->id.'-'.$choice->value;
									@endphp
									<input autocomplete="off" class="btn-check register-status" id="{{ $input_id }}" name="status[{{ $member->id }}]" type="radio" value="{{ $choice->value }}" @checked($status === $choice) />
									<label class="btn btn-sm btn-outline-{{ $choice->color() }}" for="{{ $input_id }}" title="{{ $choice->label() }}">
										@if ($choice === RegisterStatus::Unmarked)
											—
										@else
											{{ $choice->label() }}
										@endif
									</label>
								@endforeach
							</div>
						</td>
						<td>
							<input class="form-control form-control-sm" maxlength="255" name="notes[{{ $member->id }}]" placeholder="Optional note" type="text" value="{{ $entry?->notes }}" />
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="4">No members to take a register for. Add members with an instrument to {{ $ensemble->name }} first.</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>

	@if ($members->isNotEmpty())
		<div class="card-footer d-print-none d-flex align-items-center">
			@php
				$last_marked = $entries->sortByDesc('updated_at')->first();
			@endphp
			@if ($last_marked)
				<span class="text-secondary">Last saved {{ $last_marked->updated_at->diffForHumans() }}@if ($last_marked->marked_by) by {{ $last_marked->marked_by->name }}@endif.</span>
			@else
				<span class="text-secondary">This register has not been taken yet.</span>
			@endif
			<button class="ms-auto btn btn-primary" type="submit">Save register</button>
		</div>
	@endif
</form>
