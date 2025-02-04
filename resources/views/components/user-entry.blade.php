@props(['user', 'add_route' => true, 'secondary_info' => null])

@php
	if ($secondary_info == null) {
	    $secondary_info = $user->role_description;
	}
@endphp

<a {!! $add_route ? "href='" . route('users.show', ['user' => $user]) . "'" : '' !!} class="py-1 nav-link d-flex lh-1 text-reset">
	<x-avatar :user="$user" size="sm" />
	<div class="d-none d-xl-block ps-2">
		<div>{{ $user->name }}</div>
		<div class="mt-1 small text-muted">{{ $secondary_info }}</div>
	</div>
</a>
