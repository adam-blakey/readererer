@props(['user'])

<a class="py-1 nav-link d-flex lh-1 text-reset" href="{{ route('users.show', ['user' => $user]) }}">
	<x-avatar :user="$user" size="sm" />
	<x-name-and-role :user="$user" />
</a>
