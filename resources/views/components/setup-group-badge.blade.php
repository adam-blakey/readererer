{{-- Pass a falsy $size to render the badge at the default badge size, so it lines up with neighbouring badges. --}}
@props(['setup_group', 'show_as_dot' => false, 'show_with_van' => false, 'size' => 'lg', 'tooltip' => true])

@php
	// The badge shows a bare week number — or, as a dot, nothing at all — so the
	// hover text is the only place it says what it stands for.
	$tooltip_text = $setup_group->label;

	if ($show_with_van) {
	    $tooltip_text .= ' — van driver';
	}
@endphp

@if ($show_as_dot && $show_with_van)
    <span class="badge badge-dot bg-{{ $setup_group->color }} badge-notification text-{{ $setup_group->color }}-fg p-0" @if ($tooltip) aria-label="{{ $tooltip_text }}" data-bs-toggle="tooltip" title="{{ $tooltip_text }}" @endif><x-icon name="truck" /></span>
@elseif ($show_as_dot)
    <span class="badge badge-dot bg-{{ $setup_group->color }} badge-notification" @if ($tooltip) aria-label="{{ $tooltip_text }}" data-bs-toggle="tooltip" title="{{ $tooltip_text }}" @endif></span>
@else
    <span @class(['badge', 'badge-'.$size => $size, 'bg-'.$setup_group->color->cssClass(), 'text-'.$setup_group->color->cssClass().'-fg']) @if ($tooltip) aria-label="{{ $tooltip_text }}" data-bs-toggle="tooltip" title="{{ $tooltip_text }}" @endif>{{ $setup_group->week }}</span>
@endif
