@props(['status', 'ensemble_id', 'term_date_id', 'member_id', 'assume_attending', 'allow_change_to_unknown'])

@php
	$input_id = 'status-t' . $term_date_id . 'm' . $member_id;

	$display_status = ($assume_attending and $status == App\Enums\AttendanceStatus::Unknown) ? App\Enums\AttendanceStatus::Attending : $status;
@endphp

<button class="form-check-input three-state-checkbox" data-allow-change-to-unknown="{{ $allow_change_to_unknown }}" data-assume-attending="{{ $assume_attending }}" data-original-value="{{ $display_status }}" onclick="switchThreeStateCheckbox(this, '{{ $input_id }}')" type="button"></button>
