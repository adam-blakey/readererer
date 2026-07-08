@props(['term_dates', 'ensembles' => null])

@php
    $ensembles = $ensembles ?? collect();
    $dates = ($term_dates ?? collect())->sortBy('start_datetime');
@endphp

<div class="table-responsive">
    @if ($dates->isEmpty())
        <p class="p-3 mb-0 text-muted">Nothing scheduled.</p>
    @else
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Setup group</th>
                    <th>Van driver</th>
                    <th>Emails</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dates as $td)
                    <tr>
                        <td>
                            @if ($td->start_datetime->isPast())
                                <strike>{{ $td->name }}</strike>
                            @else
                                {{ $td->name }}
                            @endif
                        </td>
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
                            @forelse ($td->email_logs as $log)
                                <div>
                                    <x-icon name="{{ $log->status === \App\Enums\EmailStatus::Failed ? 'alert-square' : 'check' }}" />
                                    {{ $log->subject }} — {{ $log->recipients->count() }} recipient(s), {{ $log->created_at->diffForHumans() }}
                                </div>
                            @empty
                                <span class="text-muted">No emails sent yet.</span>
                            @endforelse
                        </td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                @can('sendNotifications', $td)
                                    <form method="POST" action="{{ route('term-dates.send-attendance-list', $td) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm bg-orange text-orange-fg">
                                            <x-icon name="list-check" />
                                            Send attendance list now
                                        </button>
                                    </form>
                                    @if ($td->setup_group)
                                        <form method="POST" action="{{ route('term-dates.send-setup-reminder', $td) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm bg-info text-info-fg">
                                                <x-icon name="bell-ringing" />
                                                Resend setup reminder
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                                @foreach ($ensembles as $ensemble)
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
