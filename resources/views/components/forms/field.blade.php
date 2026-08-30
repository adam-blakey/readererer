@php use Illuminate\Support\Collection; @endphp
@props(['name', 'data'])

@php
    $error_message = $errors->first($name);
    $has_error = $error_message != null || $error_message != '';
    // Old input wins over the model's value, so a form that comes back from a
    // failed validation keeps what was submitted — including on a create form,
    // where the model's value is null.
    $value = old($name, $data['value'] ?? null);
    $classes = ['form-control', 'is-invalid' => $has_error, 'required' => $data['required']];
    // The colour picker is a swatch grid rather than a single input, so it sits
    // outside the input-icon wrapper the other field types use.
    $is_color = $data['type'] === 'color';
@endphp

<div @class(['col-md-'.$data['width']])>
    <label @class(['col-3', 'col-form-label', 'required' => $data['required']])>{{ $data['label'] }}</label>
    <!-- TODO: fix alignment of icon when there is an error present -->
    @if (!$is_color)
    <div class="input-icon">
        <span class="input-icon-addon">
            <x-icon :name="$data['icon']" />
        </span>
    @endif
        @switch($data['type'])
            @case('class')
                <!-- TODO: style nice -->
                <select name="{{ $name }}{{ $data['select_multiple'] ? '[]' : '' }}" @class($classes) {{ $data['select_multiple'] ? 'multiple' : '' }}>
                    @foreach($data['options'] as $option)
                        @php
                            $selected = ($data['value'] instanceof Collection) ? $data['value']->contains($option->id) : $data['value'] == $option;
                        @endphp
                        <option value="{{ $option->id }}" {{ $selected ? 'selected' : null }}>{{ $option->name }}</option>
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
            @case('color')
                <x-forms.color-picker
                    :name="$name"
                    :options="$data['options']"
                    :value="$value ?: $data['default_option']"
                    :required="$data['required']"
                    :label="$data['label']"
                    :has_error="$has_error"
                />
                @break
            @case('enum')
                @php
                    // The value may be an enum instance off the model, or the raw
                    // backing value coming back from old input.
                    $selected = $value ?: $data['default_option'];
                    $selected = $selected instanceof \UnitEnum ? enum_case_value($selected) : $selected;
                @endphp
                <select
                    name="{{ $name }}"
                    id="select-{{ $name }}-{{ uniqid() }}"
                    @class(['form-select', 'is-invalid' => $has_error])
                    @required($data['required'])
                >
                    @unless($data['required'])
                        <option value="" {{ $selected === null ? 'selected' : '' }}>—</option>
                    @endunless
                    @foreach($data['options'] as $optionValue => $optionLabel)
                        <option
                            value="{{ $optionValue }}"
                            {{ $selected !== null && (string) $selected === (string) $optionValue ? 'selected' : '' }}
                        >
                            {{ $optionLabel }}
                        </option>
                    @endforeach
                </select>
                @break
            @default
                <input type="text" name="{{ $name }}" value="{{ $value }}" @class($classes) placeholder="{{ $data['label'] }}" @required($data['required'])>
        @endswitch
        @if($has_error)
            <div @class(['invalid-feedback', 'd-block' => $is_color])>{{ $error_message }}</div>
        @endif
    @if (!$is_color)
    </div>
    @endif
</div>
