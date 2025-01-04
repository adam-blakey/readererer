@props(['users'])

<div class="table-responsive">
	<table class="table table-vcenter card-table">
		<thead>
			<tr>
				<th>Name</th>
				<th class="w-1"></th>
			</tr>
		</thead>
		<tbody>
			@if ($users->isEmpty())
				<tr>
					<td colspan="5">No users found.</td>
				</tr>
			@else
				@foreach ($users as $user)
					<tr>
						<td><a href="{{ route('users.show', ['user' => $user]) }}">{{ $user->name }}</a></td>
						<td><a href="{{ route('users.edit', ['user' => $user]) }}">Edit</a></td>
					</tr>
				@endforeach
			@endif
		</tbody>
	</table>
</div>
