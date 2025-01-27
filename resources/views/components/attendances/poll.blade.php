@props(['members', 'term', 'ensemble'])

@push('scripts')
	<script src="/js/three-state-checkbox.js"></script>
@endpush

@php
	$assume_attending = config('app.readererer_assume_attending');
@endphp

<div class="table-responsive">
	<form action="{{ route('attendance.poll-store', ['ensemble' => $ensemble, 'term' => $term]) }}" method="POST">
		@csrf
		<table class="table table-vcenter card-table text-nowrap datatable">
			<thead>
				<tr>
					<th>Members</th>
					@foreach ($term->term_dates as $term_date)
						<th class="text-center poll-date">
							{{ $term_date->start_datetime->format('M') }}<br />
							<span class="poll-date-date">{{ $term_date->start_datetime->format('j') }}</span><br />
							{{ $term_date->start_datetime->format('D') }}<br />
							{{ $term_date->start_datetime->format('G:i') }}<br />
							{{ $term_date->end_datetime->format('G:i') }}<br />
						</th>
					@endforeach
				</tr>
			</thead>
			<tbody>
				@foreach ($members as $member)
					<tr>
						<td>
							{{ $member->name }}
						</td>
						@foreach ($term->term_dates as $term_date)
							<td class="w-1">
								@php
									$attendance = $member->attendances->where('term_date_id', $term_date->id)->sortByDesc('created_at')->first();
									$attendance_value = $attendance->status ?? App\Enums\AttendanceStatus::Unknown;
								@endphp
								<x-forms.input-three-state-checkbox :$assume_attending :member_id="$member->id" :status="$attendance_value" :term_date_id="$term_date->id" />
							</td>
						@endforeach
					</tr>
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
