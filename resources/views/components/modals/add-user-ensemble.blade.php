
@php use App\Models\User; @endphp
@props(['ensemble'])

@php
    $users = User::all()->sortBy('name');
    $instrumentFamilies = App\Models\InstrumentFamily::all()->sortBy('name');
@endphp

<div class="modal modal-blur fade" id="modal-add-user-ensemble" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('ensembles.add_user', ['ensemble' => $ensemble]) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h4 class="modal-title">Add user to {{ $ensemble->name }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Assigned To</label>
                        <select id="user_id" name="user_id" class="form-select" required>
                            <option value="">Select person</option>
                            @foreach($users as $user)
                                @if($user->ensembles->contains('id', $ensemble->id))
                                    @php
                                        $pivot = $user->ensembles->firstWhere('id', $ensemble->id)->pivot;
                                        $instrument = App\Models\InstrumentFamily::find($pivot->instrument_family_id)?->name ?? 'No instrument';
                                    @endphp
                                    <option value="{{ $user->id }}" disabled>{{ $user->name }} ({{ $instrument }} in {{ $pivot->seat_row }}{{ $pivot->seat_column }})</option>
                                @else
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Instrument Family</label>
                        <select name="instrument_family_id" class="form-select" required>
                            <option value="">Select instrument family</option>
                            @foreach($instrumentFamilies as $instrumentFamily)
                                <option value="{{ $instrumentFamily->id }}">{{ $instrumentFamily->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Seat Row</label>
                                <input type="text" name="seat_row" class="form-control" placeholder="A">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Seat Column</label>
                                <input type="text" name="seat_column" class="form-control" placeholder="1">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary ms-auto">
                        Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
