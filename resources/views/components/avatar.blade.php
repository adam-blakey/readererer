@props(['user', 'size' => null])

@php
	$classes = 'avatar';

	if ($size) {
	    if (in_array($size, ['xxs', 'xs', 'sm', 'md', 'lg', 'xl', 'xxl'])) {
	        $classes .= ' avatar-' . $size;
	    }
	}
@endphp

@if ($user->image)
	<span class="{{ $classes }}" style="background-image: url({{ $user->image }})"></span>
@else
	<span class="{{ $classes }}">{{ $user->initials }}</span>
@endif
