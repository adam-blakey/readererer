@props(['entities'])

<div class="table-responsive">
	@if ($entities->isEmpty())
		<p class="p-5">No results found.</p>
	@else
		@php
			$attributes = $entities->first()->getVisible();
			$sortables = $entities->first()->sortables;
			$casts = $entities->first()->casts();
		@endphp

		<table class="table table-vcenter card-table">
			<thead>
				<tr>
					@foreach ($attributes as $attribute)
						<th>
							@if (in_array($attribute, $sortables))
								<x-larasort-link name="{{ $attribute }}" />
							@else
								{{ $attribute }}
							@endif
						</th>
					@endforeach
				</tr>
			</thead>
			<tbody>
				@foreach ($entities as $entity)
					<tr>
						@foreach ($attributes as $attribute)
							<td>
								@if ($entity->$attribute instanceof Illuminate\Support\Collection)
									@foreach ($entity->$attribute as $item)
										@php
											$class_path = get_class($item);
											$class_split = explode('\\', $class_path);
											$class_name = strtolower(end($class_split));
											$route_name = $class_name . 's.show';
										@endphp

										<a href="{{ route($route_name, [$class_name => $item]) }}">{{ $item->name }}</a>{{ $loop->last ? '' : ',' }}
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
					</tr>
				@endforeach
			</tbody>
		</table>
	@endif
</div>
