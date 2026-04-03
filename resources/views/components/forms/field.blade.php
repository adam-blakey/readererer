@props(['name', 'data'])

@php
    $error_message = $errors->first($name);
    $has_error = $error_message != null || $error_message != '';
    $value = (isset($data['value'])) ? old($name, $data['value']) : null;
    $classes = ['form-control', 'is-invalid' => $has_error, 'required' => $data['required']]
@endphp

<div @class(['col-md-'.$data['width']])>
    <label @class(['col-3', 'col-form-label', 'required' => $data['required']])>{{ $data['label'] }}</label>
    <!-- TODO: fix alignment of icon when there is an error present -->
    <div class="input-icon">
        <span class="input-icon-addon">
            <x-icon :name="$data['icon']" />
        </span>
        @switch($data['type'])
            @case('class')
                <!-- TODO: style nice -->
                <select name="{{ $name }}{{ $data['select_multiple'] ? '[]' : '' }}" @class($classes) {{ $data['select_multiple'] ? 'multiple' : '' }}>
                    @foreach($data['options'] as $option)
                        <option value="{{ $option->id }}" {{ $data['value']->contains($option->id) ? 'selected' : null }}>{{ $option->name }}</option>
                    @endforeach
                </select>
                @break
            @case('textarea')
                <textarea name="{{ $name }}" @class($classes) rows="3" placeholder="{{ $data['label'] }}" @required($data['required'])>{{ $value }}</textarea>
                @break
            @case('number')
                <input name="{{ $name }}" type="number" value="{{ $value }}" @class($classes) placeholder="{{ $data['label'] }}" @required($data['required']) />
                @break
            @case('checkbox')
                @break
            @case('date')
                @break
            @case('enum')
                <!-- TODO: This apparently isn't working correctly -->
                @php($selected = ($value) ? : $data['default_option'])
                <select name="{{ $name }}" class="form-select" style="padding-left: 2.5rem" @required($data['required'])>
                    @foreach($data['options'] as $value => $case)
                        <option value="{{ $value }}" {{ $selected == $case ? 'selected' : '' }}>
                            {{ $case->name }}
                        </option>
                    @endforeach
                </select>
                @break
            @default
                <input type="text" name="{{ $name }}" value="{{ $value }}" @class($classes) placeholder="{{ $data['label'] }}" @required($data['required'])>
        @endswitch
        @if($has_error)
            <div class="invalid-feedback">{{ $error_message }}</div>
        @endif
    </div>
</div>
