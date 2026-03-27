@props(['ensemble', 'groupedUsers', 'page_name'])

<x-layout :$page_name>
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="my-0 font-bold">{{ $page_name }}</h1>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <button class="btn btn-primary" id="save-seating-plan">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card mb-3">
                <div class="card-header">
                    <h2 class="card-title">Unassigned</h2>
                </div>
                <div class="card-body">
                    <div class="row" data-row="unassigned">
                        @if(isset($groupedUsers['unassigned']))
                            @foreach($groupedUsers['unassigned'] as $user)
                                <div class="col-md-3" data-user-id="{{ $user->id }}">
                                    <x-user-entry :user="$user" />
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            @foreach($groupedUsers as $row => $users)
                @if($row !== 'unassigned')
                    <div class="card mb-3">
                        <div class="card-header">
                            <h2 class="card-title">Row {{ $row }}</h2>
                        </div>
                        <div class="card-body">
                            <div class="row" data-row="{{$row}}">
                                @foreach($users as $user)
                                    <div class="col-md-3" data-user-id="{{ $user->id }}">
                                        <x-user-entry :user="$user" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
    @push('scripts')
        @vite('resources/js/seating-plan.js')
    @endpush
</x-layout>
