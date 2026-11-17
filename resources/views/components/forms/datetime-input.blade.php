@props(['name', 'value' => null, 'id' => null, 'required' => false, 'invalid' => false, 'initial' => null])

@php
    [$date, $time] = array_pad(explode('T', (string) $value, 2), 2, '');
    [$initial_date, $initial_time] = array_pad(explode('T', (string) $initial, 2), 2, '');
@endphp

<div class="input-group">
    <input @class(['form-control', 'is-invalid' => $invalid]) id="{{ $id }}" name="{{ $name }}[date]" type="date" value="{{ $date }}" @if ($initial !== null) data-initial="{{ $initial_date }}" @endif @required($required)>
    <x-forms.time-input aria-label="{{ __('Time') }}" class="flex-grow-0 w-auto" :initial="$initial === null ? null : $initial_time" :invalid="$invalid" :name="$name.'[time]'" :required="$required" :value="$time" />
</div>
