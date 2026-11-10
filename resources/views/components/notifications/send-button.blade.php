@props(['termDate', 'mailable', 'route', 'icon', 'class' => 'bg-info text-info-fg'])

@php
	$times_sent = $termDate->timesSent($mailable);
	$label = match ($mailable) {
		\App\Mail\AttendanceListMail::class => $times_sent ? __('Resend attendance list now (:count previous)', ['count' => $times_sent]) : __('Send attendance list now'),
		\App\Mail\SetupReminderMail::class => $times_sent ? __('Resend setup reminder (:count previous)', ['count' => $times_sent]) : __('Send setup reminder'),
		\App\Mail\VanDriverReminderMail::class => $times_sent ? __('Resend van driver reminder (:count previous)', ['count' => $times_sent]) : __('Send van driver reminder'),
	};
@endphp

<form method="POST" action="{{ route($route, $termDate) }}">
	@csrf
	<button type="submit" class="btn btn-sm {{ $class }}">
		<x-icon name="{{ $icon }}" />
		{{ $label }}
	</button>
</form>
