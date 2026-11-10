@props(['term_date', 'ensembles', 'last_updated'])

@use('App\Enums\AttendanceStatus')

@php
	$show_setup_groups = config('app.readererer_setup_groups');
	$show_van_drivers = config('app.readererer_van_drivers');
	$van_driver = $show_van_drivers ? $term_date->inferred_van_driver : null;
	$status_colours = [
	    AttendanceStatus::Attending->value => 'green',
	    AttendanceStatus::NotAttending->value => 'red',
	    AttendanceStatus::Depping->value => 'lime',
	    AttendanceStatus::Unknown->value => 'secondary',
	];
@endphp

<x-layout>
	<div class="container-xl">
		<x-card-row>
			<div class="col-12">
				<x-card>
					<div class="card-header">
						<div>
							<h2 class="mb-1 card-title">{{ $term_date->name }}</h2>
							<div class="list-inline list-inline-dots mb-0 text-secondary">
								<span class="list-inline-item">
									@if ($term_date->is_concert)
										<span class="badge bg-primary text-primary-fg">{{ __('Concert') }} ({{ $term_date->concert_ensemble_names }})</span>
									@else
										<span class="badge bg-gray text-muted">{{ __('Rehearsal') }}</span>
									@endif
								</span>
								<span class="list-inline-item">{{ $term_date->relative_label }}</span>
								<span class="list-inline-item"><x-a href="{{ route('terms.show', $term_date->term) }}">{{ $term_date->term->name }}</x-a></span>
								<span class="list-inline-item"><x-icon name="map-pin" /> <x-location-link :location="$term_date->inferred_location" /></span>
								@if ($term_date->remindersDueAt())
									<span class="list-inline-item" title="{{ $term_date->remindersDueAt() }}"><x-icon name="bell" /> {{ __('Reminders :time', ['time' => $term_date->remindersDueAt()->diffForHumans()]) }}</span>
								@endif
								@if ($term_date->isLockedByReminders())
									<span class="list-inline-item"><x-icon name="lock" /> {{ __('Poll locked') }}</span>
								@endif
								<span class="list-inline-item">
									{{ $last_updated ? __('Attendance last updated :time', ['time' => $last_updated->diffForHumans()]) : __('No attendance replies yet') }}
								</span>
							</div>
						</div>
						@can('update', $term_date->term)
							<div class="card-actions">
								<x-a class="btn" href="{{ route('terms.edit', $term_date->term) }}"><x-icon name="pencil" /> {{ __('Edit') }}</x-a>
							</div>
						@endcan
					</div>
				</x-card>
			</div>

			<div class="col-lg-8">
				@forelse ($ensembles as $ensemble)
					@php
						$totals = member_status_totals($ensemble->users, $term_date, $ensemble);
					@endphp
					<x-card class="mb-3">
						<div class="card-header">
							<div>
								<h3 class="mb-1 card-title">{{ $ensemble->name }}</h3>
								<div class="btn-list">
									<span class="badge bg-green-lt">{{ __(':count attending', ['count' => $totals['attending']]) }}</span>
									<span class="badge bg-red-lt">{{ __(':count not attending', ['count' => $totals['not_attending']]) }}</span>
									@isset($totals['unknown'])
										<span class="badge bg-secondary-lt">{{ __(':count unknown', ['count' => $totals['unknown']]) }}</span>
									@endisset
								</div>
							</div>
							<div class="card-actions btn-list">
								<x-a class="btn btn-sm" href="{{ route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $term_date->term]) }}">
									<x-icon name="square-check" /> {{ __('Poll') }}
								</x-a>
								@can('viewAny', App\Models\RegisterEntry::class)
									<x-a class="btn btn-sm" href="{{ route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $term_date]) }}">
										<x-icon name="list-check" /> {{ __('Register') }}
									</x-a>
								@endcan
								@if ($ensemble->seating_plan_enabled)
									<x-a class="btn btn-sm bg-orange text-orange-fg" href="{{ route('ensembles.seating-plan.download', ['ensemble' => $ensemble, 'termDate' => $term_date]) }}" target="_blank">
										<x-icon name="armchair" /> {{ __('Seating plan') }}
									</x-a>
								@endif
							</div>
						</div>
						<div class="table-responsive">
							<table class="table table-vcenter card-table">
								<thead>
									<tr>
										<th>{{ __('Member') }}</th>
										<th>{{ __('Status') }}</th>
										<th>{{ __('Last updated') }}</th>
									</tr>
								</thead>
								<tbody>
									@forelse ($ensemble->users as $member)
										@php
											$attendance = $member->latestAttendanceFor($term_date, $ensemble);
											$status = ($attendance?->status ?? AttendanceStatus::Unknown)->effective();
										@endphp
										<tr>
											<td>{{ $member->name }}</td>
											<td>
												<span class="badge bg-{{ $status_colours[$status->value] }}-lt">{{ $attendance?->status_text ?? $status->label() }}</span>
											</td>
											<td class="text-secondary">
												@if ($attendance)
													<span title="{{ $attendance->created_at }}">{{ $attendance->created_at->diffForHumans() }}</span>
													@if ($attendance->edit_user && $attendance->edit_user->isNot($member))
														{{ __('by :name', ['name' => $attendance->edit_user->name]) }}
													@endif
												@else
													{{ __('Never') }}
												@endif
											</td>
										</tr>
									@empty
										<tr>
											<td class="text-muted" colspan="3">{{ __('This ensemble has no members.') }}</td>
										</tr>
									@endforelse
								</tbody>
							</table>
						</div>
					</x-card>
				@empty
					<x-card class="mb-3">
						<div class="card-body text-muted">{{ __('No ensemble rehearses or performs on this date.') }}</div>
					</x-card>
				@endforelse
			</div>

			<div class="col-lg-4">
				@if ($show_setup_groups || $show_van_drivers)
					<x-card class="mb-3">
						<div class="card-header">
							<h3 class="card-title">{{ $show_setup_groups ? __('Setup group') : __('Van driver') }}</h3>
						</div>
						<div class="card-body">
							@if ($show_setup_groups)
								@if ($term_date->setup_group)
									<div class="mb-2"><x-setup-group-badge :setup_group="$term_date->setup_group" :size="false" /></div>
									@foreach ($term_date->setup_group->members as $member)
										<x-user-entry :user="$member" />
									@endforeach
								@else
									<p class="text-muted">{{ __('No setup group is on duty for this date') }}</p>
								@endif
							@endif
							@if ($show_van_drivers)
								<div class="mt-3">
									<div class="subheader mb-1">{{ __('Van driver') }}</div>
									@if ($van_driver)
										<x-user-entry :user="$van_driver" />
									@else
										<span class="badge bg-red text-red-fg">{{ __('No van driver!') }}</span>
									@endif
								</div>
							@endif
						</div>
					</x-card>
				@endif

				@can('sendNotifications', $term_date)
					<x-card class="mb-3">
						<div class="card-header">
							<h3 class="card-title">{{ __('Send emails') }}</h3>
						</div>
						<div class="card-body btn-list">
							<x-notifications.send-button :term-date="$term_date" :mailable="\App\Mail\AttendanceListMail::class" route="term-dates.send-attendance-list" icon="list-check" />
							@if ($show_setup_groups && $term_date->setup_group)
								<x-notifications.send-button :term-date="$term_date" :mailable="\App\Mail\SetupReminderMail::class" route="term-dates.send-setup-reminder" icon="bell-ringing" class="bg-info-lt text-info-lt-fg" />
							@endif
							@if ($van_driver)
								<x-notifications.send-button :term-date="$term_date" :mailable="\App\Mail\VanDriverReminderMail::class" route="term-dates.send-van-driver-reminder" icon="truck" />
							@endif
						</div>
					</x-card>
				@endcan
			</div>

			<div class="col-12">
				<x-card>
					<div class="card-header">
						<h3 class="card-title">{{ __('Emails sent') }}</h3>
					</div>
					<x-notifications.table :logs="$term_date->email_logs" />
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>
