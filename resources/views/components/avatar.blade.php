@props(['user', 'size' => null, 'show_setup_group' => false, 'tooltip' => true])

@php
	$classes = 'avatar';

	if ($size) {
	    if (in_array($size, ['xxs', 'xs', 'sm', 'md', 'lg', 'xl', 'xxl'])) {
	        $classes .= ' avatar-' . $size;
	    }
	}

	// An avatar is initials or a picture, and the name beside it is hidden on
	// narrow screens, so hovering it should say who this is — and, when the
	// setup group badge is riding on it, which group they set up with.
	$show_group_badge = $show_setup_group && $user->setup_group != null;

	$tooltip_text = $user->name;

	if ($show_group_badge) {
	    $tooltip_text .= ' — ' . $user->setup_group->label;
	}
@endphp

@if ($user->image)
	<span class="{{ $classes }}" style="background-image: url({{ $user->image }})" @if ($tooltip) data-bs-toggle="tooltip" title="{{ $tooltip_text }}" @endif>
        @if ($show_group_badge)
            <x-setup-group-badge :setup_group="$user->setup_group" size="sm" :tooltip="!$tooltip" />
        @endif
    </span>
@else
	<span class="{{ $classes }}" @if ($tooltip) data-bs-toggle="tooltip" title="{{ $tooltip_text }}" @endif>
        {{ $user->initials }}
        @if ($show_group_badge)
            <x-setup-group-badge :setup_group="$user->setup_group" size="sm" :tooltip="!$tooltip" />
        @endif
    </span>
@endif
