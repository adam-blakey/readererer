@props(['term_dates', 'ensembles' => null])

@php
    $ensembles = $ensembles ?? collect();
    // Only ensembles that run a seating plan offer a plan to download.
    $seating_plan_ensembles = $ensembles->filter(fn ($ensemble) => $ensemble->seating_plan_enabled);
    $dates = ($term_dates ?? collect())->sortBy('start_datetime');
@endphp

<div class="table-responsive">
    @if ($dates->isEmpty())
        <p class="p-3 mb-0 text-muted">Nothing scheduled.</p>
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
                    <th>Date</th>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Setup group</th>
                    <th>Van driver</th>
                    <th>Emails</th>
                    <th>Actions</th>
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
                                <span class="badge bg-green text-green-fg">Concert @if($td->concert_ensemble) ({{ $td->concert_ensemble->name }}) @endif</span>
                            @else
                                <span class="badge bg-gray text-muted">Rehearsal</span>
                            @endif
                        </td>
                        <td>
                            @if ($td->setup_group != null)
                                <x-setup-group-badge :setup_group="$td->setup_group" />
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($td->inferred_van_driver == null)
                                <span class="badge bg-red text-red-fg">No van driver!</span>
                            @else
                                {{ $td->inferred_van_driver->name }}
                            @endif
                        </td>
                        <td>
                            @php $logs = $td->email_logs; @endphp
                            @if ($logs->isEmpty())
                                <span class="text-muted">No emails sent yet.</span>
                            @else
                                @php $latest = $logs->first(); $rest = $logs->slice(1); @endphp
                                <div>
                                    <x-icon name="{{ $latest->status === \App\Enums\EmailStatus::Failed ? 'alert-square' : 'check' }}" />
                                    {{ $latest->subject }} — {{ $latest->recipients->count() }} recipient(s), {{ $latest->created_at->diffForHumans() }}
                                </div>
                                @if ($rest->isNotEmpty())
                                    <a class="d-inline-block small" data-bs-toggle="collapse" href="#email-history-{{ $td->id }}" role="button" aria-expanded="false">
                                        Show {{ $rest->count() }} earlier
                                    </a>
                                    <div class="collapse" id="email-history-{{ $td->id }}">
                                        @foreach ($rest as $log)
                                            <div class="text-secondary">
                                                <x-icon name="{{ $log->status === \App\Enums\EmailStatus::Failed ? 'alert-square' : 'check' }}" />
                                                {{ $log->subject }} — {{ $log->recipients->count() }} recipient(s), {{ $log->created_at->diffForHumans() }}
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
                                            Send attendance list now
                                        </button>
                                    </form>
                                    @if ($td->setup_group)
                                        <form method="POST" action="{{ route('term-dates.send-setup-reminder', $td) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm bg-info-lt text-info-lt-fg">
                                                <x-icon name="bell-ringing" />
                                                Resend setup reminder
                                            </button>
                                        </form>
                                    @endif
                                    @if($td->inferred_van_driver)
                                        <form method="POST" action="{{ route('term-dates.send-van-driver-reminder', $td) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm bg-info text-info-fg">
                                                <x-icon name="truck" />
                                                Send van driver reminder
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                                @foreach ($seating_plan_ensembles as $ensemble)
                                    <x-a class="btn btn-sm bg-orange text-orange-fg" href="{{ route('seating-plan.download', ['ensemble' => $ensemble, 'termDate' => $td]) }}" target="_blank">
                                        <x-icon name="armchair" />
                                        Seating plan: {{ $ensemble->name }}
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
