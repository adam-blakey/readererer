@props(['setup_group', 'show_as_dot' => false, 'show_with_van' => false])
@php($colorClass = color_name_to_css_class($setup_group->color) ?? 'secondary')

@if ($show_as_dot && $show_with_van)
    <span class="badge badge-dot bg-{{ $colorClass }} badge-notification text-{{ $colorClass }}-fg p-0"><x-icon name="truck" /></span>
@elseif ($show_as_dot)
    <span class="badge badge-dot bg-{{ $colorClass }} badge-notification"></span>
@else
    <span class="badge badge-lg bg-{{ $colorClass }} text-{{ $colorClass }}-fg">{{ $setup_group->week }}</span>
@endif
