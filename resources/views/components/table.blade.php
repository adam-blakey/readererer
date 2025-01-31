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
								<x-larasort-link name="{{ $clean_attribute }}" />
							@else
								{{ $clean_attribute }}
							@endif
						</th>
					@endforeach
					@if (Auth::user()?->can('viewAny', App\Models\Attendance::class))
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
								@elseif ($entity->$attribute instanceof Illuminate\Support\Collection)
									@foreach ($entity->$attribute as $item)
										<x-clickable-model :model="$item" />{{ $loop->last ? '' : ',' }}
									@endforeach
								@else
									@if (array_key_exists($attribute, $casts))
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
									@else
										{{ $entity->$attribute }}
									@endif
								@endif
							</td>
						@endforeach
						@if (Auth::user()?->can('viewAny', App\Models\Attendance::class))
							<td><a href="{{ route($edit_route_name, [$class_name => $entity]) }}">Edit</a></td>
						@endif
					</tr>
				@endforeach
			</tbody>
		</table>
	@endif
</div>
