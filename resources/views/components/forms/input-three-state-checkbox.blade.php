@props(['status', 'ensemble_id', 'term_date_id', 'member_id', 'assume_attending'])

@php
	$input_id = 'status-e' . $ensemble_id . 't' . $term_date_id . 'm' . $member_id;

	$display_status = ($assume_attending and $status == App\Enums\AttendanceStatus::Unknown) ? App\Enums\AttendanceStatus::Attending : $status;
@endphp

<button class="form-check-input three-state-checkbox" data-assume-attending="{{ $assume_attending }}" data-original-value="{{ $display_status }}" onclick="switchThreeStateCheckbox(this, '{{ $input_id }}')" type="button"></button>
