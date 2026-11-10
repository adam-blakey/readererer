@props(['term_dates', 'ensembles'])

@use('App\Enums\EmailStatus')
@use('App\Enums\RegisterStatus')

@php
	$seating_plan_ensembles = $ensembles->filter(fn ($ensemble) => $ensemble->seating_plan_enabled);
	$show_setup_groups = config('app.readererer_setup_groups');
	$show_van_drivers = config('app.readererer_van_drivers');
@endphp

<div class="table-responsive">
	<table class="table table-vcenter card-table">
		<thead>
			<tr>
				<th>{{ __('Date') }}</th>
				<th>{{ __('Time') }}</th>
				<th class="w-1">{{ __('Type') }}</th>
				<th>{{ __('Term') }}</th>
				<th>{{ __('Polls') }}</th>
				<th>{{ __('Registers') }}</th>
				<th>{{ __('Emails') }}</th>
				<th>{{ __('Actions') }}</th>
			</tr>
		</thead>
		<tbody>
			@forelse ($term_dates as $term_date)
				@php
					$applicable_ensembles = $ensembles->filter(fn ($ensemble) => $term_date->appliesToEnsemble($ensemble));
				@endphp
				<tr>
					<td><x-term-date-link :$term_date>{{ $term_date->date_label }}</x-term-date-link></td>
					<td class="text-nowrap">{{ $term_date->time_label }}</td>
					<td>
						@if ($term_date->is_concert)
							<span class="badge bg-primary text-primary-fg text-wrap text-start">{{ __('Concert') }} ({{ $term_date->concert_ensemble_names }})</span>
						@else
							<span class="badge bg-gray text-muted">{{ __('Rehearsal') }}</span>
						@endif
					</td>
					<td>{{ $term_date->term?->name }}</td>
					<td>
						@if ($term_date->term && $applicable_ensembles->isNotEmpty())
							<div class="btn-list">
								@foreach ($applicable_ensembles as $ensemble)
									<x-a class="btn btn-sm" href="{{ route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $term_date->term]) }}" data-bs-toggle="tooltip" title="{{ __('View poll') }}">
										<x-icon name="square-check" />
										{{ $ensemble->name }}
									</x-a>
								@endforeach
							</div>
						@else
							<span class="text-muted">—</span>
						@endif
					</td>
					<td>
						@if ($applicable_ensembles->isEmpty())
							<span class="text-muted" data-bs-toggle="tooltip" title="{{ __('No ensemble rehearses or performs on this date, so there is no register to take') }}">—</span>
						@else
							<div class="btn-list">
								@foreach ($applicable_ensembles as $ensemble)
									@php
										$entries = $term_date->register_entries->where('ensemble_id', $ensemble->id);
										$present = $entries->whereIn('status', [RegisterStatus::Present, RegisterStatus::Late])->count();
									@endphp
									<x-a class="btn btn-sm" href="{{ route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $term_date]) }}">
										<x-icon name="list-check" />
										{{ $ensemble->name }}
										@if ($entries->isNotEmpty())
											<span class="ms-1 badge bg-green-lt">{{ __(':count present', ['count' => $present]) }}</span>
										@else
											<span class="ms-1 badge bg-secondary-lt">{{ __('Not taken') }}</span>
										@endif
									</x-a>
								@endforeach
							</div>
						@endif
					</td>
					<td>
						@php $logs = $term_date->email_logs; @endphp
						@if ($logs->isEmpty())
							<span class="text-muted">{{ __('No emails sent yet.') }}</span>
						@else
							@php $latest = $logs->first(); $rest = $logs->slice(1); @endphp
							@php $latest_failed = $latest->status === EmailStatus::Failed; @endphp
							<div>
								<span data-bs-toggle="tooltip" title="{{ $latest_failed ? __('This email failed to send') : __('This email was sent') }}">
									<x-icon name="{{ $latest_failed ? 'alert-square' : 'check' }}" />
								</span>
								{{ $latest->subject }} — {{ trans_choice(':count recipient|:count recipients', $latest->recipients->count()) }}, {{ $latest->created_at->diffForHumans() }}
							</div>
							@if ($rest->isNotEmpty())
								<a class="d-inline-block small" data-bs-toggle="collapse" href="#email-history-{{ $term_date->id }}" role="button" aria-expanded="false">
									{{ __('Show :count earlier', ['count' => $rest->count()]) }}
								</a>
								<div class="collapse" id="email-history-{{ $term_date->id }}">
									@foreach ($rest as $log)
										@php $log_failed = $log->status === EmailStatus::Failed; @endphp
										<div class="text-secondary">
											<span data-bs-toggle="tooltip" title="{{ $log_failed ? __('This email failed to send') : __('This email was sent') }}">
												<x-icon name="{{ $log_failed ? 'alert-square' : 'check' }}" />
											</span>
											{{ $log->subject }} — {{ trans_choice(':count recipient|:count recipients', $log->recipients->count()) }}, {{ $log->created_at->diffForHumans() }}
										</div>
									@endforeach
								</div>
							@endif
						@endif
					</td>
					<td>
						<div class="btn-list">
							@can('sendNotifications', $term_date)
								<x-notifications.send-button :term-date="$term_date" :mailable="\App\Mail\AttendanceListMail::class" route="term-dates.send-attendance-list" icon="list-check" />
								@if ($show_setup_groups && $term_date->setup_group)
									<x-notifications.send-button :term-date="$term_date" :mailable="\App\Mail\SetupReminderMail::class" route="term-dates.send-setup-reminder" icon="bell-ringing" class="bg-info-lt text-info-lt-fg" />
								@endif
								@if ($show_van_drivers && $term_date->inferred_van_driver)
									<x-notifications.send-button :term-date="$term_date" :mailable="\App\Mail\VanDriverReminderMail::class" route="term-dates.send-van-driver-reminder" icon="truck" />
								@endif
							@endcan
							@foreach ($seating_plan_ensembles as $ensemble)
								<x-a class="btn btn-sm bg-orange text-orange-fg" href="{{ route('ensembles.seating-plan.download', ['ensemble' => $ensemble, 'termDate' => $term_date]) }}" target="_blank">
									<x-icon name="armchair" />
									{{ __('Seating plan: :name', ['name' => $ensemble->name]) }}
								</x-a>
							@endforeach
						</div>
					</td>
				</tr>
			@empty
				<tr>
					<td colspan="8">{{ __('No rehearsals or concerts have been scheduled yet.') }}</td>
				</tr>
			@endforelse
		</tbody>
	</table>
</div>
