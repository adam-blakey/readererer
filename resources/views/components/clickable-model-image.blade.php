@props(['model', 'route' => 'show'])

@php
	$route_name = get_route_name_from_model($model);
	$class_name = get_class_name_from_model($model);
@endphp

@if (Route::has($route_name))
	<x-a :route="$route_name" :model="$model"><img alt="{{ $model->name }} image" class="rounded" src="{{ $model->image }}" style="width: 50px;"></x-a>
@else
	<img alt="{{ $model->name }} image" class="rounded" src="{{ $model->image }}" style="width: 50px;">
@endif
