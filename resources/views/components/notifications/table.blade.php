@props(['logs'])

@if ($logs->isEmpty())
	<div class="card-body text-muted">{{ __('No notifications have been sent yet.') }}</div>
@else
	<div class="table-responsive">
		<table class="table table-vcenter card-table">
			<thead>
				<tr>
					<th>{{ __('Sent') }}</th>
					<th>{{ __('Type') }}</th>
					<th>{{ __('Subject') }}</th>
					<th>{{ __('Date concerned') }}</th>
					<th>{{ __('Status') }}</th>
					<th>{{ __('Recipients') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($logs as $log)
					<tr>
						<td class="text-nowrap" title="{{ $log->created_at }}">{{ $log->created_at->diffForHumans() }}</td>
						<td>
							<span class="badge bg-azure text-azure-fg">{{ $log->type_label }}</span>
						</td>
						<td>{{ $log->subject }}</td>
						<td>
							@if ($log->termDate?->term && auth()->user()?->can('view', $log->termDate->term))
								<x-a href="{{ route('terms.show', $log->termDate->term) }}#term-date-{{ $log->termDate->id }}">{{ $log->termDate->name }}</x-a>
							@elseif ($log->termDate)
								{{ $log->termDate->name }}
							@else
								<span class="text-muted">—</span>
							@endif
						</td>
						<td>
							@if ($log->status === \App\Enums\EmailStatus::Sent)
								<span class="badge bg-green text-green-fg">{{ __('Sent') }}</span>
							@elseif ($log->status === \App\Enums\EmailStatus::Failed)
								<span class="badge bg-red text-red-fg">{{ __('Failed') }}</span>
							@else
								<span class="badge bg-yellow text-yellow-fg">{{ __('Pending') }}</span>
							@endif
						</td>
						<td>
							@forelse ($log->recipients as $recipient)
								<div>
									<x-icon name="{{ $recipient->status === \App\Enums\EmailStatus::Failed ? 'alert-square' : 'check' }}" />
									{{ $recipient->name ?? $recipient->email }}
									@if ($recipient->error_message)
										<span class="text-danger" title="{{ $recipient->error_message }}">({{ __('failed') }})</span>
									@endif
								</div>
							@empty
								<span class="text-muted">{{ __('Nobody') }}</span>
							@endforelse
							@feature(\App\Features\ResendFailedNotifications::class)
								@can('send.notifications')
									@if ($log->recipients->contains('status', \App\Enums\EmailStatus::Failed) && $log->resendableMailable())
										<form method="POST" action="{{ route('notifications.resend', $log) }}" class="mt-2">
											@csrf
											<button type="submit" class="btn btn-sm bg-info text-info-fg">
												<x-icon name="send" />
												{{ __('Resend to failed recipients') }}
											</button>
										</form>
									@endif
								@endcan
							@endfeature
						</td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>
@endif
