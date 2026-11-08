@props(['model', 'route' => 'show'])

@php
	$route_name = get_route_name_from_model($model);
	$class_name = get_class_name_from_model($model);
@endphp

@if (Route::has($route_name))
	@if (method_exists($model, 'trashed') && $model->trashed())
		<x-a :href="route($route_name, [$model, 'with_trashed' => 1])" :route="$route_name" :model="$model">{{ $model->name }}</x-a>
	@else
		<x-a :route="$route_name" :model="$model">{{ $model->name }}</x-a>
	@endif
@else
	{{ $model->name }}
@endif
