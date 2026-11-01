@props(['user'])

<div class="entry-text d-none d-sm-block ps-2">
	<div class="text-truncate">{{ $user->name }}</div>
	<div class="mt-1 small text-muted text-truncate">{{ $user->role_description }}</div>
</div>
