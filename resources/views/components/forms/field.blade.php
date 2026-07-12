@php use Illuminate\Support\Collection; @endphp
@props(['name', 'data'])

@php
    $error_message = $errors->first($name);
    $has_error = $error_message != null || $error_message != '';
    $value = (isset($data['value'])) ? old($name, $data['value']) : null;
    $classes = ['form-control', 'is-invalid' => $has_error, 'required' => $data['required']];
    $has_color_preview = $data['type'] === 'enum' && ($name === 'color');
    $selected_color_class = $has_color_preview ? (color_name_to_css_class((string) ($value ?: ($data['default_option'] ?? null))) ?? 'secondary') : null;
@endphp

<div @class(['col-md-'.$data['width']])>
    <label @class(['col-3', 'col-form-label', 'required' => $data['required']])>{{ $data['label'] }}</label>
    <!-- TODO: fix alignment of icon when there is an error present -->
    <div class="input-icon">
        <span class="input-icon-addon">
            @if ($has_color_preview)
                <span data-color-preview class="avatar avatar-xs rounded-circle bg-{{ $selected_color_class }}"></span>
            @else
                <x-icon :name="$data['icon']" />
            @endif
        </span>
        @switch($data['type'])
            @case('class')
                <!-- TODO: style nice -->
                <select name="{{ $name }}{{ $data['select_multiple'] ? '[]' : '' }}" @class($classes) {{ $data['select_multiple'] ? 'multiple' : '' }}>
                    @foreach($data['options'] as $option)
                        @php($selected = ($data['value'] instanceof Collection) ? $data['value']->contains($option->id) : $data['value'] == $option)
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
            @case('enum')
                @php($selected = $value ?: $data['default_option'])
                <select
                    name="{{ $name }}"
                    @class(['form-select', 'is-invalid' => $has_error])
                    style="padding-left: 2.5rem"
                    @required($data['required'])
                    @if ($has_color_preview)
                        onchange="const color=this.options[this.selectedIndex].dataset.colorClass||'secondary';const swatch=this.closest('.input-icon').querySelector('[data-color-preview]');if(swatch){swatch.className='avatar avatar-xs rounded-circle bg-'+color;}"
                    @endif
                >
                    @foreach($data['options'] as $optionValue => $optionLabel)
                        @php($optionColorClass = $has_color_preview ? (color_name_to_css_class((string) $optionValue) ?? 'secondary') : null)
                        <option
                            value="{{ $optionValue }}"
                            {{ (string) $selected === (string) $optionValue ? 'selected' : '' }}
                            @if ($has_color_preview)
                                data-color-class="{{ $optionColorClass }}"
                            @endif
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
            <div class="invalid-feedback">{{ $error_message }}</div>
        @endif
    </div>
</div>
