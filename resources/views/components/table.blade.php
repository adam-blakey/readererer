@props(['entities', 'entities_name'])

<div class="table-responsive">
	@if ($entities->isEmpty())
		<p class="p-5">No {{ $entities_name }} found.</p>
	@else
		@php
			$attributes = $entities->first()->getVisible();
		@endphp

		<table class="table table-vcenter card-table">
			<thead>
				<tr>
					@foreach ($attributes as $attribute)
						<th><x-larasort-link name="{{ $attribute }}" /></th>
					@endforeach
				</tr>
			</thead>
			<tbody>
				@foreach ($entities as $entity)
					<tr>
						@foreach ($attributes as $attribute)
							<td>{{ $entity->$attribute }}</td>
						@endforeach
					</tr>
				@endforeach
			</tbody>
		</table>
	@endif
</div>
