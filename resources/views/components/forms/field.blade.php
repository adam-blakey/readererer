@php use Illuminate\Support\Carbon; @endphp
@props(['name', 'data'])

@php
    $error_message = $errors->first($name);
    $has_error = filled($error_message);

    $type = $data['type'];
    $required = (bool) $data['required'];
    $multiple = $type === 'class' && $data['select_multiple'];

    // Multi-selects post an array, so the control's name has to say so.
    $input_name = $multiple ? $name.'[]' : $name;
    $id = 'field-'.$name;

    $value = old($name, $data['value'] ?? null);

    // The colour picker is a swatch grid and the boolean switch is a control of
    // its own shape; neither sits inside the input-icon wrapper the rest use.
    $is_color = $type === 'color';
    $show_icon = ! $is_color && $type !== 'boolean';

    // A textarea or a multi-select is taller than a single row, so the icon has
    // to sit against the first row rather than the middle of the control.
    $icon_at_top = $type === 'textarea' || $multiple;

    $control_classes = ['form-control', 'is-invalid' => $has_error];
    $select_classes = ['form-select', 'is-invalid' => $has_error];

    // Relationship selects post ids. The value reaching them is a model, a
    // collection of models, or — after a failed validation pass — raw ids from
    // the old input, so normalise all three to a collection of id strings.
    $selected_ids = collect();

    if ($type === 'class') {
        $selected_ids = collect(is_iterable($value) ? $value : [$value])
            ->map(fn ($entry) => is_object($entry) ? ($entry->id ?? null) : $entry)
            ->reject(fn ($id) => $id === null || $id === '')
            ->map(fn ($id) => (string) $id);
    }

    // Date inputs only accept their own formats, and the value may arrive as a
    // Carbon instance (a date cast), a database string, or old input.
    $date_value = null;

    if ($type === 'date' || $type === 'datetime') {
        $date_format = $type === 'date' ? 'Y-m-d' : 'Y-m-d\TH:i';

        if ($value instanceof \DateTimeInterface) {
            $date_value = $value->format($date_format);
        } elseif (filled($value)) {
            $date_value = rescue(fn () => Carbon::parse($value)->format($date_format), $value, false);
        }
    }
@endphp

<div @class(['col-md-'.$data['width'], 'mb-3'])>
    {{-- The colour picker is a radio group with no single control to point at. --}}
    <label @if (! $is_color) for="{{ $id }}" @endif @class(['form-label', 'required' => $required])>{{ $data['label'] }}</label>

    @if ($show_icon)
    <div @class(['input-icon', 'input-icon-top' => $icon_at_top])>
        <span class="input-icon-addon">
            <x-icon :name="$data['icon']" />
        </span>
    @endif
        @switch($type)
            @case('class')
                <select
                    name="{{ $input_name }}"
                    id="{{ $id }}"
                    @class($select_classes)
                    @required($required)
                    @if ($multiple) multiple size="{{ max(min(count($data['options']), 8), 3) }}" @endif
                >
                    @unless ($required || $multiple)
                        <option value="" @selected($selected_ids->isEmpty())>—</option>
                    @endunless
                    @foreach ($data['options'] as $option)
                        <option value="{{ $option->id }}" @selected($selected_ids->contains((string) $option->id))>{{ $option->name }}</option>
                    @endforeach
                </select>
                @break
            @case('textarea')
                <textarea name="{{ $name }}" id="{{ $id }}" @class($control_classes) rows="3" placeholder="{{ $data['label'] }}" @required($required)>{{ $value }}</textarea>
                @break
            @case('number')
                <input type="number" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" @class($control_classes) placeholder="{{ $data['label'] }}" @required($required) />
                @break
            @case('email')
                <input type="email" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" @class($control_classes) placeholder="{{ $data['label'] }}" @required($required) />
                @break
            @case('password')
                {{-- Deliberately no value: the stored one is a hash, and it has no business being in the page. --}}
                <input type="password" name="{{ $name }}" id="{{ $id }}" @class($control_classes) placeholder="{{ $data['label'] }}" autocomplete="new-password" @required($required) />
                @break
            @case('boolean')
                <div class="form-check form-switch">
                    {{-- An unchecked box posts nothing, so pair it with a hidden off value. --}}
                    <input type="hidden" name="{{ $name }}" value="0" />
                    <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="1" @class(['form-check-input', 'is-invalid' => $has_error]) @checked((bool) $value) />
                </div>
                @break
            @case('date')
            @case('datetime')
                <input type="{{ $type === 'date' ? 'date' : 'datetime-local' }}" name="{{ $name }}" id="{{ $id }}" value="{{ $date_value }}" @class($control_classes) @required($required) />
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
                    id="{{ $id }}"
                    @class($select_classes)
                    @required($required)
                >
                    @unless($required)
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
                <input type="text" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" @class($control_classes) placeholder="{{ $data['label'] }}" @required($required) />
        @endswitch
    @if ($show_icon)
    </div>
    @endif

    @if ($has_error)
        <div class="invalid-feedback d-block">{{ $error_message }}</div>
    @endif
</div>
