@php use App\Enums\AttendanceStatus; use App\Models\TermDate; @endphp
@props(['members', 'term', 'ensemble'])

@php
	$assume_attending = config('app.readererer_assume_attending');

	$term_dates = $term
	    ->term_dates
	    ->filter(function(TermDate $td) use ($ensemble) {
	        return
	            $td->concert_ensemble_id == null ||
	            $td->concert_ensemble_id == $ensemble->id;
	    });

	$spans_multiple_years = $term->term_dates->isNotEmpty() && $term->earliest_date->year != $term->latest_date->year;
@endphp

<div class="table-responsive">
	<table class="table table-vcenter card-table text-nowrap datatable">
		<thead>
			<x-attendances.heading :$term_dates :show_year="$spans_multiple_years" :ensemble="$ensemble" />
		</thead>
		<tbody>
			@if ($members->isEmpty())
				<tr>
					<td colspan="{{ $term_dates->count() + 1 }}">No members found.</td>
				</tr>
			@endif
			@foreach ($members as $member)
				<tr>
					<td>
						<x-user-entry :add_route="false" :secondary_info="App\Models\InstrumentFamily::find($member->ensembles->where('id', $ensemble->id)->first()->pivot->instrument_family_id)->name ?? ''" :user="$member" show_setup_group="true" />
					</td>
					@foreach ($term_dates as $term_date)
						@php
							$status = latest_attendance_status($member, $term_date);
							$display_status = ($assume_attending and $status == AttendanceStatus::Unknown) ? AttendanceStatus::Attending : $status;
						@endphp
						<td class="w-1 text-center {{ ((int)($term_date->concert_ensemble_id) === (int)($ensemble->id)) ? 'bg-primary-subtle' : '' }}">
							@switch ($display_status)
								@case (AttendanceStatus::Attending)
									<span class="text-green" title="Attending"><x-icon name="check" /></span>
									@break
								@case (AttendanceStatus::NotAttending)
									<span class="text-red" title="Not attending"><x-icon name="x" /></span>
									@break
								@default
									<span class="text-muted" title="Unknown"><x-icon name="question-mark" /></span>
							@endswitch
						</td>
					@endforeach
				</tr>
			@endforeach
		</tbody>
		<tfoot>
			<tr>
				<th>Attending</th>
				@foreach ($term_dates as $term_date)
					@php
						$totals = member_status_totals($members, $term_date);
					@endphp
					<th class="w-1 text-center {{ ((int)($term_date->concert_ensemble_id) === (int)($ensemble->id)) ? 'bg-primary-subtle' : '' }}">
						{{ $totals['attending'] }}&hairsp;/&hairsp;{{ $members->count() }}
					</th>
				@endforeach
			</tr>
		</tfoot>
	</table>
</div>
