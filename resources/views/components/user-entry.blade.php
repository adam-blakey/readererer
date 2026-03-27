@props(['user', 'add_route' => true, 'secondary_info' => null, 'show_setup_group' => false, 'remove_from_ensemble' => null, 'draggable' => false])

@php
    if ($secondary_info == null) {
        $secondary_info = $user->role_description;
    }
@endphp

<div class="py-1 nav-link d-flex lh-1 text-reset {{ $draggable ? 'cursor-grab' : '' }}">
    <x-avatar :user="$user" size="sm" :$show_setup_group />
    <div class="d-none d-xl-block ps-2">
        <div>
            {{ $user->name }}
            <span class="seating-position-changed" style="display: none;">*</span>
        </div>
        <div class="mt-1 small text-muted">{{ $secondary_info }}</div>
    </div>
    <div class="ms-auto align-self-center">
        <span class="badge bg-blue-lt seating-position"></span>
    </div>
    @if ($remove_from_ensemble != null)
        <div class="ms-auto align-self-center">
            <form method="POST" action="{{ route('ensembles.remove_user', [$remove_from_ensemble, $user]) }}" onsubmit="return confirm('Are you sure you want to archive this record?');" onclick="event.stopPropagation()">
                @csrf
                @method('POST')
                <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
            </form>
        </div>
    @endif
</div>
