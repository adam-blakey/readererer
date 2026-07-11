@props(['ensemble', 'grouped_users', 'unassigned_users', 'upcoming_term_dates', 'page_name', 'page_subname'])

<x-layout :$page_name :$page_subname>
    <div class="container-xl">
        <x-card-row>
            <div class="col-md-12">
                <x-card>
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ $page_subname }}
                        </h3>
                        <div class="card-actions btn-list">
                            @if($upcoming_term_dates->isNotEmpty())
                                <div class="dropdown">
                                    <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <x-icon name="file-type-pdf" />
                                        Download
                                    </button>
                                    <div class="dropdown-menu">
                                        @foreach($upcoming_term_dates as $term_date)
                                            <x-a class="dropdown-item" href="{{ route('seating-plan.download', ['ensemble' => $ensemble, 'termDate' => $term_date]) }}" target="_blank">
                                                {{ $term_date->name }}
                                                @if($term_date->concert_ensemble_id)
                                                    <span class="badge bg-green text-green-fg ms-2">Concert</span>
                                                @endif
                                            </x-a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <button class="btn btn-primary" id="save-seating-plan">
                                Save
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="card mb-3 seating-row" data-row="unassigned">
                            <div class="card-header">
                                <h2 class="card-title">Unassigned</h2>
                            </div>
                            <div class="card-body">
                                @foreach($unassigned_users as $instrument => $users)
                                    <h3 class="mb-1">
                                        @if($color = instrument_family_color($users->first()->pivot->instrument_family_id))
                                            <span class="badge badge-dot bg-{{ $color }} me-1"></span>
                                        @endif
                                        {{ $instrument }}
                                    </h3>
                                    <div class="row min-h-2 mb-2 drop-container">
                                        @foreach($users as $user)
                                            <div class="col-md-3 user-entry" data-user-id="{{ $user->id }}" data-original-row="{{ $user->original_seat_row }}" data-original-column="{{ $user->original_seat_column }}">
                                                <x-user-entry :user="$user" :add_route="false" :draggable="true" :secondary_info="$user->instrument_name" :show_seating_position="true" :accent_color="instrument_family_color($user->pivot->instrument_family_id)" />
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                                <div class="row min-h-2 drop-container"></div>
                            </div>
                        </div>

                        <div id="seating-rows">
                            @foreach($grouped_users as $row => $users)
                                <div class="card mb-3 seating-row" data-row="{{ $row }}">
                                    <div class="card-header">
                                        <h2 class="card-title">Row {{ $row }}</h2>
                                    </div>
                                    <div class="card-body">
                                        <div class="row min-h-2 drop-container">
                                            @foreach($users as $user)
                                                <div class="col-md-3 user-entry" data-user-id="{{ $user->id }}" data-original-row="{{ $user->original_seat_row }}" data-original-column="{{ $user->original_seat_column }}">
                                                    <x-user-entry :user="$user" :add_route="false" :draggable="true" :secondary_info="$user->instrument_name" :show_seating_position="true" :accent_color="instrument_family_color($user->pivot->instrument_family_id)" />
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-card>
            </div>
        </x-card-row>
    </div>

    @push('scripts')
        @vite('resources/js/seating-plan.js')
    @endpush
</x-layout>
