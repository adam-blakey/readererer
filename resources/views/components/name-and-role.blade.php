@props(['user'])

<div class="d-none d-xl-block ps-2">
	<div>{{ $user->name }}</div>
	<div class="mt-1 small text-muted">{{ $user->role_description }}</div>
</div>
