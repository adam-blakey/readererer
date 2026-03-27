@props(['ensemble', 'grouped_users', 'page_name', 'page_subname'])

<x-layout :$page_name :$page_subname>
    <div class="container-xl">
        <x-card-row>
            <div class="col-md-12">
                <x-card>
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ $page_subname }}
                        </h3>
                        <div class="card-actions">
                            <button class="btn btn-primary" id="save-seating-plan">
                                Save
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row" data-row="unassigned">
                            @if(isset($grouped_users['unassigned']))
                                @foreach($grouped_users['unassigned'] as $user)
                                    <div class="col-md-3" data-user-id="{{ $user->id }}" data-original-row="{{ $user->original_seat_row }}" data-original-column="{{ $user->original_seat_column }}">
                                        <x-user-entry :user="$user" />
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        @foreach($grouped_users as $row => $users)
                            @if($row !== 'unassigned')
                                <div class="card mb-3 seating-row" data-row="{{ $row }}">
                                    <div class="card-header">
                                        <h2 class="card-title">Row {{ $row }}</h2>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($users as $user)
                                                <div class="col-md-3" data-user-id="{{ $user->id }}" data-original-row="{{ $user->original_seat_row }}" data-original-column="{{ $user->original_seat_column }}">
                                                    <x-user-entry :user="$user" :add_route="false" :draggable="true" :secondary_info="$user->instrument_name" />
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        <div class="card mb-3 seating-row" data-row="{{ ++$row }}" style="display: none;">
                            <div class="card-header">
                                <h2 class="card-title">Row {{ $row }}</h2>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                </div>
                            </div>
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
