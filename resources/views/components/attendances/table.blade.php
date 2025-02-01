@props(['attendances'])

<div class="table-responsive">
	<table class="table table-vcenter card-table">
		<thead>
			<tr>
				<th>User</th>
				<th>Editing user</th>
				<th><x-larasort-link display_name="updated at" name="updated_at" /></th>
				<th>Term date</th>
				<th>Poll</th>
				<th>Status</th>
			</tr>
		</thead>
		<tbody>
			@if ($attendances->isEmpty())
				<tr>
					<td colspan="5">No attendance updates found.</td>
				</tr>
			@else
				@foreach ($attendances as $attendance)
					<tr>
						<td><a href="{{ route('users.show', ['user' => $attendance->user]) }}">{{ $attendance->user->name }}</a></td>
						<td><a href="{{ route('users.show', ['user' => $attendance->edit_user]) }}">{{ $attendance->edit_user->name }}</a></td>
						<td>{{ $attendance->updated_at }}</td>
						<td>{{ $attendance->term_date->start_datetime->diffForHumans() }}</td>
						<td><a href="{{ route('attendance.poll', ['ensemble' => $attendance->ensemble, 'term' => $attendance->term_date->term]) }}">{{ $attendance->name }}</a></td>
						<td>{{ $attendance->status_text }}</td>
					</tr>
				@endforeach
			@endif
		</tbody>
	</table>
</div>
