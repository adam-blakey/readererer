@props(['term_dates', 'ensembles' => null])

@php
    $ensembles = $ensembles ?? collect();
    // Only ensembles that run a seating plan offer a plan to download.
    $seating_plan_ensembles = $ensembles->filter(fn ($ensemble) => $ensemble->seating_plan_enabled);
    $dates = ($term_dates ?? collect())->sortBy('start_datetime');
    $can_take_registers = Gate::allows('viewAny', App\Models\RegisterEntry::class);
@endphp

<div class="table-responsive">
    @if ($dates->isEmpty())
        <p class="p-3 mb-0 text-muted">{{ __('Nothing scheduled.') }}</p>
    @else
        <table class="table table-vcenter card-table">
            <colgroup>
                <col style="width: 14%">
                <col style="width: 8%">
                <col style="width: 15%">
                <col style="width: 8%">
                <col style="width: 13%">
                <col style="width: 20%">
                <col style="width: 22%">
            </colgroup>
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Time') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Setup group') }}</th>
                    <th>{{ __('Van driver') }}</th>
                    <th>{{ __('Emails') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dates as $td)
                    <tr>
                        <td>
                            @if ($td->start_datetime->isPast())
                                <strike>{{ $td->date_label }}</strike>
                            @else
                                {{ $td->date_label }}
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $td->time_label }}</td>
                        <td>
                            @if ($td->concert_ensemble_id)
                                <span class="badge bg-green text-green-fg">{{ __('Concert') }} @if($td->concert_ensemble) ({{ $td->concert_ensemble->name }}) @endif</span>
                            @else
                                <span class="badge bg-gray text-muted">{{ __('Rehearsal') }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($td->setup_group != null)
                                <x-setup-group-badge :setup_group="$td->setup_group" />
                            @else
                                <span class="text-muted" data-bs-toggle="tooltip" title="{{ __('No setup group is on duty for this date') }}">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($td->inferred_van_driver == null)
                                <span class="badge bg-red text-red-fg">{{ __('No van driver!') }}</span>
                            @else
                                {{ $td->inferred_van_driver->name }}
                            @endif
                        </td>
                        <td>
                            @php $logs = $td->email_logs; @endphp
                            @if ($logs->isEmpty())
                                <span class="text-muted">{{ __('No emails sent yet.') }}</span>
                            @else
                                @php $latest = $logs->first(); $rest = $logs->slice(1); @endphp
                                @php $latest_failed = $latest->status === \App\Enums\EmailStatus::Failed; @endphp
                                <div>
                                    <span data-bs-toggle="tooltip" title="{{ $latest_failed ? __('This email failed to send') : __('This email was sent') }}">
                                        <x-icon name="{{ $latest_failed ? 'alert-square' : 'check' }}" />
                                    </span>
                                    {{ $latest->subject }} — {{ trans_choice(':count recipient|:count recipients', $latest->recipients->count()) }}, {{ $latest->created_at->diffForHumans() }}
                                </div>
                                @if ($rest->isNotEmpty())
                                    <a class="d-inline-block small" data-bs-toggle="collapse" href="#email-history-{{ $td->id }}" role="button" aria-expanded="false">
                                        {{ __('Show :count earlier', ['count' => $rest->count()]) }}
                                    </a>
                                    <div class="collapse" id="email-history-{{ $td->id }}">
                                        @foreach ($rest as $log)
                                            @php $log_failed = $log->status === \App\Enums\EmailStatus::Failed; @endphp
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
                                @can('sendNotifications', $td)
                                    <form method="POST" action="{{ route('term-dates.send-attendance-list', $td) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm bg-info text-info-fg">
                                            <x-icon name="list-check" />
                                            {{ __('Send attendance list now') }}
                                        </button>
                                    </form>
                                    @if ($td->setup_group)
                                        <form method="POST" action="{{ route('term-dates.send-setup-reminder', $td) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm bg-info-lt text-info-lt-fg">
                                                <x-icon name="bell-ringing" />
                                                {{ __('Resend setup reminder') }}
                                            </button>
                                        </form>
                                    @endif
                                    @if($td->inferred_van_driver)
                                        <form method="POST" action="{{ route('term-dates.send-van-driver-reminder', $td) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm bg-info text-info-fg">
                                                <x-icon name="truck" />
                                                {{ __('Send van driver reminder') }}
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                                @if ($can_take_registers)
                                    @foreach ($ensembles->filter(fn ($ensemble) => $td->appliesToEnsemble($ensemble)) as $ensemble)
                                        <x-a class="btn btn-sm" href="{{ route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $td]) }}">
                                            <x-icon name="list-check" />
                                            {{ __('Register: :name', ['name' => $ensemble->name]) }}
                                        </x-a>
                                    @endforeach
                                @endif
                                @foreach ($seating_plan_ensembles as $ensemble)
                                    <x-a class="btn btn-sm bg-orange text-orange-fg" href="{{ route('seating-plan.download', ['ensemble' => $ensemble, 'termDate' => $td]) }}" target="_blank">
                                        <x-icon name="armchair" />
                                        {{ __('Seating plan: :name', ['name' => $ensemble->name]) }}
                                    </x-a>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
