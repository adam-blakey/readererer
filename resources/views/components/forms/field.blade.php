@php use Illuminate\Support\Collection; @endphp
@props(['name', 'data'])

@php
    $error_message = $errors->first($name);
    $has_error = $error_message != null || $error_message != '';
    $value = (isset($data['value'])) ? old($name, $data['value']) : null;
    $classes = ['form-control', 'is-invalid' => $has_error, 'required' => $data['required']];
    $has_color_preview = $data['type'] === 'enum' && ($name === 'color');
    $selected_color_class = $has_color_preview ? (color_name_to_css_class($value ?: ($data['default_option'] ?? null)) ?? 'secondary') : null;
@endphp

<div @class(['col-md-'.$data['width']])>
    <label @class(['col-3', 'col-form-label', 'required' => $data['required']])>{{ $data['label'] }}</label>
    <!-- TODO: fix alignment of icon when there is an error present -->
    @if (!$has_color_preview)
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
            @case('enum')
                @php
                    $selected = $value ?: $data['default_option'];
                @endphp
                <select
                    name="{{ $name }}"
                    id="select-{{ $name }}-{{ uniqid() }}"
                    @class(['form-select', 'is-invalid' => $has_error])
                    @required($data['required'])
                >
                    @foreach($data['options'] as $optionValue => $optionLabel)
                        @php
                            $valSelected = $selected instanceof \UnitEnum ? ($selected instanceof \BackedEnum ? $selected->value : $selected->name) : $selected;
                            $valOptionValue = $optionValue instanceof \UnitEnum ? ($optionValue instanceof \BackedEnum ? $optionValue->value : $optionValue->name) : $optionValue;
                            $optionColorClass = $has_color_preview ? (color_name_to_css_class((string) $valOptionValue) ?? 'secondary') : null;
                        @endphp
                        <option
                            value="{{ $valOptionValue }}"
                            {{ (string) $valSelected === (string) $valOptionValue ? 'selected' : '' }}
                            @if ($has_color_preview)
                                data-color-class="{{ $optionColorClass }}"
                            @endif
                        >
                            {{ $optionLabel }}
                        </option>
                    @endforeach
                </select>
                @if ($has_color_preview)
                    <script type="module">
                        document.addEventListener('DOMContentLoaded', function () {
                            if (window.TomSelect) {
                                new TomSelect('select[name="{{ $name }}"]', {
                                    copyClassesToDropdown: false,
                                    dropdownParent: 'body',
                                    controlInput: '<input>',
                                    render: {
                                        option: function(data, escape) {
                                            var colorClass = data.$option.getAttribute('data-color-class') || 'secondary';
                                            return '<div><span class="dropdown-item-indicator"><span class="avatar avatar-xs bg-' + escape(colorClass) + '"></span></span>' + escape(data.text) + '</div>';
                                        },
                                        item: function(data, escape) {
                                            var colorClass = data.$option.getAttribute('data-color-class') || 'secondary';
                                            return '<div><span class="dropdown-item-indicator"><span class="avatar avatar-xs bg-' + escape(colorClass) + '"></span></span>' + escape(data.text) + '</div>';
                                        }
                                    }
                                });
                            }
                        });
                    </script>
                @endif
                @break
            @default
                <input type="text" name="{{ $name }}" value="{{ $value }}" @class($classes) placeholder="{{ $data['label'] }}" @required($data['required'])>
        @endswitch
        @if($has_error)
            <div class="invalid-feedback">{{ $error_message }}</div>
        @endif
    @if (!$has_color_preview)
    </div>
    @endif
</div>
