@props(['setup_group', 'show_as_dot' => false, 'show_with_van' => false, 'size' => 'lg'])

@if ($show_as_dot && $show_with_van)
    <span class="badge badge-dot bg-{{ $setup_group->color }} badge-notification text-{{ $setup_group->color }}-fg p-0"><x-icon name="truck" /></span>
@elseif ($show_as_dot)
    <span class="badge badge-dot bg-{{ $setup_group->color }} badge-notification"></span>
@else
    <span class="badge badge-{{ $size }} bg-{{ $setup_group->color }} text-{{ $setup_group->color }}-fg">{{ $setup_group->week }}</span>
@endif
