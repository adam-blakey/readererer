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
								<x-larasort-link display_name="{{ $clean_attribute }}" name="{{ $attribute }}" />
							@else
								{{ $clean_attribute }}
							@endif
						</th>
					@endforeach
                    <th>Edit</th>
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
                                @elseif (str_contains($attribute, 'color'))
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="avatar bg-{{ $entity->$attribute }} text-{{ $entity->$attribute }}-fg"></div>
                                        </div>
                                        <div class="col">
                                            {{ ucfirst($entity->$attribute) }}<br>
                                            <code>#ffffff</code> <!-- TODO: Get actual hex code -->
                                        </div>
                                    </div>

								@else
									{{ $entity->$attribute }}
								@endif
							</td>
						@endforeach
                        <td><x-a :route="$edit_route_name" :model="$entity">Edit</x-a></td>
					</tr>
				@endforeach
			</tbody>
		</table>
	@endif
</div>
