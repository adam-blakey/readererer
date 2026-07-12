@props(['attendees', 'max_avatars' => 10])

@if ($attendees->isEmpty())
	<div class="mt-1 small text-muted ps-2">No one else has confirmed they're attending yet.</div>
@else
	<div class="mt-2 ps-2">
		<div class="mb-1 small text-muted">You're playing with {{ $attendees->count() }} {{ Str::plural('other', $attendees->count()) }}:</div>
		<div class="avatar-list avatar-list-stacked">
			@foreach ($attendees->take($max_avatars) as $attendee)
				<span title="{{ $attendee->name }}"><x-avatar :user="$attendee" size="xs" /></span>
			@endforeach
			@if ($attendees->count() > $max_avatars)
				<span class="avatar avatar-xs">+{{ $attendees->count() - $max_avatars }}</span>
			@endif
		</div>
	</div>
@endif
