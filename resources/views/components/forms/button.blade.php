@props(['type' => 'submit', 'highlight' => false])

@php
	$classes = 'btn w-100';

	if ($highlight) {
	    $classes .= ' btn-primary'
    }
@endphp

@if ($type === 'submit')
	<button class="{{ $classes }}" type="submit">
		{{ $slot }}
	</button>
@else
	<a class="{{ $classes }}" type="button">
		{{ $slot }}
	</a>
@endif
