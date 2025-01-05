@props(['pieces'])

<div class="table-responsive">
	<table class="table table-vcenter card-table">
		<thead>
			<tr>
				<th>Piece name</th>
				<th>Composer</th>
				<th>List of parts</th>
				<th class="w-1"></th>
			</tr>
		</thead>
		<tbody>
			@if ($pieces->isEmpty())
				<tr>
					<td colspan="5">No pieces found.</td>
				</tr>
			@else
				@foreach ($pieces as $piece)
					<tr>
						<td><a href="{{ route('pieces.show', ['piece' => $piece]) }}">{{ $piece->name }}</td>
						<td><a href="{{ route('composers.show', ['composer' => $piece->composer]) }}">{{ $piece->composer->full_name() }}</a></td>
						<td class="text-secondary">{{ $piece->parts_string() }}</td>
						<td>
							<a href="/pieces/{{ $piece->id }}/edit">Edit</a>
						</td>
					</tr>
				@endforeach
			@endif
		</tbody>
	</table>
</div>
