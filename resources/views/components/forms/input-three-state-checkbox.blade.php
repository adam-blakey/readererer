@props(['status', 'ensemble_id', 'term_date_id', 'member_id', 'assume_attending', 'allow_change_to_unknown', 'setup_group', 'is_van_driver', 'locked' => false, 'dep_name' => null])

@php
	$input_id = 'status-t' . $term_date_id . 'm' . $member_id;
	$dep_input_id = 'dep-t' . $term_date_id . 'm' . $member_id;
	$depping = config('app.readererer_depping');

	$display_status = $status->effective();
	$is_depping = $display_status === App\Enums\AttendanceStatus::Depping;
@endphp

<span @class(['d-inline-block position-relative dep-cell', 'depping' => $is_depping])>
<button class="form-check-input three-state-checkbox" data-allow-change-to-unknown="{{ $allow_change_to_unknown }}" data-assume-attending="{{ $assume_attending }}" data-depping="{{ $depping }}" data-original-value="{{ $display_status }}" data-input-id="{{ $input_id }}" type="button" title="{{ $is_depping && $dep_name ? __('Sending a dep: :name', ['name' => $dep_name]) : '' }}" @disabled($locked)>
    @if ($setup_group != null)
        <x-setup-group-badge :$setup_group show_as_dot="false" show_with_van="{{ $is_van_driver }}" />
    @endif
</button>
@if ($depping)
	<input class="form-control form-control-sm position-absolute top-100 start-50 translate-middle-x z-3 shadow-sm dep-name" aria-label="{{ __('Name of your dep') }}" maxlength="255" name="{{ $dep_input_id }}" placeholder="{{ __('Dep\'s name') }}" type="text" value="{{ $is_depping ? $dep_name : '' }}" @disabled($locked)>
@endif
</span>
