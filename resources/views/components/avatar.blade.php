@props(['user', 'size' => null, 'show_setup_group' => false])

@php
	$classes = 'avatar';

	if ($size) {
	    if (in_array($size, ['xxs', 'xs', 'sm', 'md', 'lg', 'xl', 'xxl'])) {
	        $classes .= ' avatar-' . $size;
	    }
	}
@endphp

@if ($user->image)
	<span class="{{ $classes }}" style="background-image: url({{ $user->image }})">
        @if ($show_setup_group && $user->setup_group != null)
            <x-setup-group-badge :setup_group="$user->setup_group" />
        @endif
    </span>
@else
	<span class="{{ $classes }}">
        {{ $user->initials }}
        @if ($show_setup_group && $user->setup_group != null)
            <x-setup-group-badge :setup_group="$user->setup_group" />
        @endif
    </span>
@endif
