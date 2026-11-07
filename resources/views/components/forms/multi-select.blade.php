@props([
    'name',
    'options',
    'selected' => [],
    'label',
    'placeholder',
    'submit_on_close' => false,
])

@php
    $selected = array_map('strval', $selected);
    $chosen = array_filter($options, fn ($value) => in_array((string) $value, $selected, true), ARRAY_FILTER_USE_KEY);
    $summary = (count($chosen) === 0 || count($chosen) === count($options)) ? $placeholder : implode(', ', $chosen);
@endphp

<div class="dropdown multi-select" data-placeholder="{{ $placeholder }}" @if ($submit_on_close) data-submit-on-close @endif>
    <button type="button" class="form-select text-start text-truncate multi-select-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="{{ $label }}">
        <span class="multi-select-summary">{{ $summary }}</span>
    </button>
    <div class="dropdown-menu">
        <label class="dropdown-item form-check mb-0">
            <input type="checkbox" class="form-check-input m-0 me-2 multi-select-all" @checked(count($chosen) === count($options))>
            <span class="form-check-label">{{ __('Select all') }}</span>
        </label>
        <div class="dropdown-divider"></div>
        @foreach ($options as $value => $option_label)
            <label class="dropdown-item form-check mb-0">
                <input type="checkbox" class="form-check-input m-0 me-2 multi-select-option" name="{{ $name }}[]" value="{{ $value }}" data-label="{{ $option_label }}" @checked(array_key_exists($value, $chosen))>
                <span class="form-check-label">{{ $option_label }}</span>
            </label>
        @endforeach
    </div>
</div>
