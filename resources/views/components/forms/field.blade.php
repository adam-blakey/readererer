@props(['field'])

@php
    $error_message = $errors->first($field['name']);
    $has_error = $error_message != null;
    $value = (isset($field['value'])) ? old($field['name'], $field['value']) : null;
@endphp

<div @class(['col-md-'.$field['width']])>
    <label @class(['col-3', 'col-form-label', 'required' => $field['required']])>{{ $field['label'] }}</label>
    <!-- TODO: fix alignment of icon when there is an error present -->
    <div class="input-icon">
        <span class="input-icon-addon">
            <x-icon :name="$field['icon']" />
        </span>
        @switch($field['type'])
            @case('textarea')
                <textarea name="{{ $field['name'] }}" @class(['form-control', 'required' => $field['required']]) rows="3" placeholder="{{ $field['label'] }}" @required($field['required'])>{{ $value }}</textarea>
                @break
            @case('number')
                @break
            @case('checkbox')
                @break
            @case('date')
                @break
            @case('enum')
                <!-- TODO: This apparently isn't working correctly -->
                @php($selected = ($value) ? : $field['default_option'])
                <select name="{{ $field['name'] }}" class="form-select" style="padding-left: 2.5rem" @required($field['required'])>
                    @foreach($field['options'] as $value => $case)
                        <option value="{{ $value }}" {{ $selected == $case ? 'selected' : '' }}>
                            {{ $case->name }}
                        </option>
                    @endforeach
                </select>
                @break
            @default
                <input type="text" name="{{ $field['name'] }}" value="{{ $value }}" @class(['form-control', 'is-invalid' => $has_error, 'required' => $field['required']]) placeholder="{{ $field['label'] }}" @required($field['required'])>
        @endswitch
        @if($has_error)
            <div class="invalid-feedback">{{ $error_message }}</div>
        @endif
    </div>
</div>
