@props(['user', 'ensembles', 'instrumentFamilies'])

<div class="modal modal-blur fade" id="modal-add-ensemble-user" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('users.ensembles.attach', ['user' => $user]) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h4 class="modal-title">Add {{ $user->name }} to an ensemble</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Ensemble</label>
                        <select name="ensemble_id" class="form-select" required>
                            <option value="">Select ensemble</option>
                            @foreach($ensembles as $ensemble)
                                @if($user->ensembles->contains('id', $ensemble->id))
                                    <option value="{{ $ensemble->id }}" disabled>{{ $ensemble->name }} (already a member)</option>
                                @else
                                    <option value="{{ $ensemble->id }}">{{ $ensemble->name }}</option>
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
                        Add to ensemble
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
