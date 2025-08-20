@props(['setup_group', 'show_as_dot' => false])

@if ($show_as_dot)
    <span class="badge badge-dot bg-{{ $setup_group->color }} badge-notification"></span>
@else
    <span class="badge badge-lg bg-{{ $setup_group->color }} text-{{ $setup_group->color }}-fg">{{ $setup_group->name }}</span>
@endif
