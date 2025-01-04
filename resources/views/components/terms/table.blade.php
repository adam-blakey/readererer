@props(['terms'])

<div class="table-responsive">
	<table class="table table-vcenter card-table">
		<thead>
			<tr>
				<th>Term name</th>
				<th>Slug</th>
				<th>Visible</th>
				<th>Date range</th>
				<th class="w-1"></th>
			</tr>
		</thead>
		<tbody>
			@if ($terms->isEmpty())
				<tr>
					<td colspan="5">No terms found.</td>
				</tr>
			@else
				@foreach ($terms as $term)
					<tr>
						<td><a href="{{ route('terms.show', ['term' => $term]) }}">{{ $term->name }}</a></td>
						<td>{{ $term->slug }}</td>
						<td>{{ $term->show ? 'Y' : 'N' }}</td>
						<td>
							{{ $term->term_dates_count }} date(s) ranging {{ $term->formatted_term_date_range() }}
						</td>
						<td>
							<a href="/ensembles/{{ $term->id }}/edit">Edit</a>
						</td>
					</tr>
				@endforeach
			@endif
		</tbody>
	</table>
</div>
