{{--
    A swatch picker for the Color palette: one radio per case, rendered as a
    Tabler colour input. It is a plain radio group, so it needs no JavaScript
    and keeps native keyboard navigation and browser validation.
--}}
@props([
    'name',
    'options',
    'value' => null,
    'required' => false,
    'label' => null,
    'has_error' => false,
])

@php
    // The value may be an enum instance off the model, or the raw backing value
    // coming back from old input or a column default.
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
            <label class="form-colorinput form-colorinput-light" title="None">
                <input
                    type="radio"
                    name="{{ $name }}"
                    value=""
                    class="form-colorinput-input"
                    aria-label="None"
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
