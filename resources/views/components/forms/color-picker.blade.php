{{-- A plain radio group per Color case, so it keeps native keyboard navigation and validation without JavaScript. --}}
@props([
    'name',
    'options',
    'value' => null,
    'required' => false,
    'label' => null,
    'has_error' => false,
])

@php
    // An enum instance from the model, or a raw backing value from old input or a column default.
    $selected = $value instanceof \UnitEnum ? enum_case_value($value) : $value;
    $selected = ($selected === null || $selected === '') ? null : (string) $selected;
@endphp

<div
    class="row g-2 color-picker"
    role="radiogroup"
    @if ($label) aria-label="{{ $label }}" @endif
    @if ($has_error) aria-invalid="true" @endif
>
    @unless ($required)
        <div class="col-auto">
            <label class="form-colorinput form-colorinput-light" title="{{ __('None') }}">
                <input
                    type="radio"
                    name="{{ $name }}"
                    value=""
                    class="form-colorinput-input"
                    aria-label="{{ __('None') }}"
                    @checked($selected === null)
                >
                <span class="form-colorinput-color bg-secondary-lt"></span>
            </label>
        </div>
    @endunless

    @foreach ($options as $option_value => $option_label)
        @php
            $option_value = (string) $option_value;
            $option_class = color_name_to_css_class($option_value);
            $option_hex = color_name_to_hex($option_value);
        @endphp
        <div class="col-auto">
            <label class="form-colorinput" title="{{ $option_label }}">
                <input
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $option_value }}"
                    class="form-colorinput-input"
                    aria-label="{{ $option_label }}"
                    @required($required)
                    @checked($selected === $option_value)
                >
                <span
                    @class(['form-colorinput-color', 'bg-'.$option_class => $option_class !== null])
                    @if ($option_hex) style="background-color: {{ $option_hex }}" @endif
                ></span>
            </label>
        </div>
    @endforeach
</div>
