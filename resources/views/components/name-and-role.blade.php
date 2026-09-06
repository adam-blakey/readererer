@props(['user'])

{{-- Hidden only on the narrowest phones, where the navbar has no room for it: --}}
{{-- the avatar next to it is the menu's hit target either way. --}}
<div class="entry-text d-none d-sm-block ps-2">
	<div class="text-truncate">{{ $user->name }}</div>
	<div class="mt-1 small text-muted text-truncate">{{ $user->role_description }}</div>
</div>
