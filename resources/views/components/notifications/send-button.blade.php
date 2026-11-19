@props(['termDate', 'mailable', 'route', 'icon', 'class' => 'bg-info text-info-fg'])

@php
	$times_sent = $termDate->timesSent($mailable);
	$label = match ($mailable) {
		\App\Mail\AttendanceListMail::class => $times_sent ? __('Resend attendance list (:count previous)', ['count' => $times_sent]) : __('Send attendance list'),
		\App\Mail\SetupReminderMail::class => $times_sent ? __('Resend setup reminder (:count previous)', ['count' => $times_sent]) : __('Send setup reminder'),
		\App\Mail\VanDriverReminderMail::class => $times_sent ? __('Resend van driver reminder (:count previous)', ['count' => $times_sent]) : __('Send van driver reminder'),
	};
	$preview = config('app.readererer_email_previews')
		? (new \App\Models\EmailLog(['mailable_class' => $mailable]))->setRelation('termDate', $termDate)->resendableMailable()
		: null;
	$modal_id = 'modal-preview-'.Str::kebab(class_basename($mailable));
@endphp

<form method="POST" action="{{ route($route, $termDate) }}">
	@csrf
	@if ($preview)
		<button type="button" class="btn btn-sm {{ $class }}" data-bs-toggle="modal" data-bs-target="#{{ $modal_id }}">
			<x-icon name="{{ $icon }}" />
			{{ $label }}
		</button>
		<div class="modal modal-blur fade" id="{{ $modal_id }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modal_id }}-title" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title" id="{{ $modal_id }}-title">{{ $preview->envelope()->subject }}</h4>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
					</div>
					<div class="modal-body p-0">
						<div class="ratio ratio-4x3">
							<iframe sandbox srcdoc="{{ $preview->render() }}" title="{{ __('Email preview') }}"></iframe>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
						<button type="submit" class="btn btn-primary" {{ loading_text(__('Sending…')) }}>
							<x-icon name="send" />
							{{ __('Send') }}
						</button>
					</div>
				</div>
			</div>
		</div>
	@else
		<button type="submit" class="btn btn-sm {{ $class }}" {{ loading_text(__('Sending…')) }}>
			<x-icon name="{{ $icon }}" />
			{{ $label }}
		</button>
	@endif
</form>
