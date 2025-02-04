@props(['model', 'route' => 'show'])

@php
	$route_name = get_route_name_from_model($model);
	$class_name = get_class_name_from_model($model);
@endphp

@if (Route::has($route_name))
	<a href="{{ route($route_name, [$class_name => $model]) }}">{{ $model->name }}</a>
@else
	{{ $model->name }}
@endif
