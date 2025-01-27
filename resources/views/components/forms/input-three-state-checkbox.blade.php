@props(['status', 'ensemble_id', 'term_date_id', 'member_id'])

@php
	$input_id = 'status-e' . $ensemble_id . 't' . $term_date_id . 'm' . $member_id;
@endphp

<button class="form-check-input three-state-checkbox three-state-checkbox-unknown" onclick="switchThreeStateCheckbox(this, '{{ $input_id }}')" type="button"></button>
