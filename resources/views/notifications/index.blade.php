@props(['logs', 'page_name'])

<x-layout :$page_name page_subname="Overview of every notification email sent, with its delivery status">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					@if ($logs->isEmpty())
						<div class="text-muted">No notifications have been sent yet.</div>
					@else
						<div class="table-responsive">
							<table class="table table-vcenter card-table">
								<thead>
									<tr>
										<th>Sent</th>
										<th>Type</th>
										<th>Subject</th>
										<th>Date concerned</th>
										<th>Status</th>
										<th>Recipients</th>
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
												@if ($log->termDate?->term)
													<x-a href="{{ route('terms.show', $log->termDate->term) }}">{{ $log->termDate->name }}</x-a>
												@elseif ($log->termDate)
													{{ $log->termDate->name }}
												@else
													<span class="text-muted">—</span>
												@endif
											</td>
											<td>
												@if ($log->status === \App\Enums\EmailStatus::Sent)
													<span class="badge bg-green text-green-fg">Sent</span>
												@elseif ($log->status === \App\Enums\EmailStatus::Failed)
													<span class="badge bg-red text-red-fg">Failed</span>
												@else
													<span class="badge bg-yellow text-yellow-fg">Pending</span>
												@endif
											</td>
											<td>
												@forelse ($log->recipients as $recipient)
													<div>
														<x-icon name="{{ $recipient->status === \App\Enums\EmailStatus::Failed ? 'alert-square' : 'check' }}" />
														{{ $recipient->name ?? $recipient->email }}
														@if ($recipient->error_message)
															<span class="text-danger" title="{{ $recipient->error_message }}">(failed)</span>
														@endif
													</div>
												@empty
													<span class="text-muted">Nobody</span>
												@endforelse
											</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					@endif
				</x-card>
			</div>
		</x-card-row>
		{{ $logs->links() }}
	</div>
</x-layout>
