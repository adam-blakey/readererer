@props(['ensembles'])

<div class="table-responsive">
	<table class="table table-vcenter card-table">
		<thead>
			<tr>
				<th><x-larasort-link display_name="ensemble name" name="name" /></th>
				<th><x-larasort-link name="slug" /></th>
				<th>Image</th>
				<th><x-larasort-link display_name="visible" name="show" /></th>
				<th>Admins</th>
				<th class="w-1"></th>
			</tr>
		</thead>
		<tbody>
			@if ($ensembles->isEmpty())
				<tr>
					<td colspan="5">No ensembles found.</td>
				</tr>
			@else
				@foreach ($ensembles as $ensemble)
					<tr>
						<td><a href="{{ route('ensembles.show', ['ensemble' => $ensemble]) }}">{{ $ensemble->name }}</a></td>
						<td>{{ $ensemble->slug }}</td>
						<td>
							<img alt="{{ $ensemble->name }}" class="rounded" src="{{ $ensemble->image }}" style="width: 50px;">
						</td>
						<td>{{ $ensemble->show ? 'Y' : 'N' }}</td>
						<td>
							@foreach ($ensemble->admins as $admin)
								<a href="/users/{{ $admin->id }}">{{ $admin->name }}</a>{{ $loop->last ? '' : ',' }}
							@endforeach
						</td>
						<td>
							<a href="/ensembles/{{ $ensemble->id }}/edit">Edit</a>
						</td>
					</tr>
				@endforeach
			@endif
		</tbody>
	</table>
</div>
