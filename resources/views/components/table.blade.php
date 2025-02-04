@props(['entities'])

<div class="table-responsive">
	@if ($entities->isEmpty())
		<p class="p-5">No results found.</p>
	@else
		@php
			$attributes = $entities->first()->getVisible();
			$sortables = $entities->first()->sortables;
			$casts = $entities->first()->casts();

			$edit_route_name = get_route_name_from_model($entities->first(), 'edit');
			$class_name = get_class_name_from_model($entities->first());

			$show_edit_heading = Auth::user()?->can('update', $entities->first());
		@endphp

		<table class="table table-vcenter card-table">
			<thead>
				<tr>
					@foreach ($attributes as $attribute)
						@php
							$clean_attribute = clean_attribute_name($attribute);
						@endphp

						<th>
							@if (in_array($attribute, $sortables))
								<x-larasort-link display_name="{{ $clean_attribute }}" name="{{ $attribute }}" />
							@else
								{{ $clean_attribute }}
							@endif
						</th>
					@endforeach
					@if ($show_edit_heading)
						<th>Edit</th>
					@endif
				</tr>
			</thead>
			<tbody>
				@foreach ($entities as $entity)
					<tr>
						@foreach ($attributes as $attribute)
							<td>
								@if ($attribute === 'name')
									<x-clickable-model :model="$entity" />
								@elseif ($attribute === 'image')
									<x-clickable-model-image :model="$entity" />
								@elseif (array_key_exists($attribute, $casts))
									@switch($casts[$attribute])
										@case('boolean')
											{{ $entity->$attribute ? 'Y' : 'N' }}
										@break

										@case('datetime')
											{{ $entity->$attribute->diffForHumans() }}
										@break

										@default
											{{ $entity->$attribute }}
									@endswitch
								@elseif ($entity->$attribute instanceof Illuminate\Support\Collection)
									@foreach ($entity->$attribute as $item)
										@if (class_exists(get_class($item)))
											<x-clickable-model :model="$item" />
										@else
											{{ $item }}
										@endif
										{{ $loop->last ? '' : ',' }}
									@endforeach
								@elseif (gettype($entity->$attribute) == 'object' and class_exists(get_class($entity->$attribute)))
									<x-clickable-model :model="$entity->$attribute" />
								@else
									{{ $entity->$attribute }}
								@endif
							</td>
						@endforeach
						@if ($show_edit_heading)
							@if (Auth::user()?->can('update', $entity))
								<td><a href="{{ route($edit_route_name, [$class_name => $entity]) }}">Edit</a></td>
							@else
								<td>—</td>
							@endif
						@endif
					</tr>
				@endforeach
			</tbody>
		</table>
	@endif
</div>
