@props(['name', 'value' => null, 'id' => null, 'required' => false, 'invalid' => false, 'initial' => null])

@feature(\App\Features\TwentyFourHourTimeInputs::class)
    @php
        [$date, $time] = array_pad(explode('T', (string) $value, 2), 2, '');
        [$initial_date, $initial_time] = array_pad(explode('T', (string) $initial, 2), 2, '');
    @endphp

    <div class="input-group">
        <input @class(['form-control', 'is-invalid' => $invalid]) id="{{ $id }}" name="{{ $name }}[date]" type="date" value="{{ $date }}" @if ($initial !== null) data-initial="{{ $initial_date }}" @endif @required($required)>
        <input aria-label="{{ __('Time') }}" @class(['form-control flex-grow-0 w-auto', 'is-invalid' => $invalid]) maxlength="5" name="{{ $name }}[time]" size="5" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" placeholder="HH:MM" title="{{ __('A 24-hour time, such as 19:30') }}" type="text" value="{{ $time }}" @if ($initial !== null) data-initial="{{ $initial_time }}" @endif @required($required)>
    </div>
@else
    <input @class(['form-control', 'is-invalid' => $invalid]) id="{{ $id }}" name="{{ $name }}" type="datetime-local" value="{{ $value }}" @if ($initial !== null) data-initial="{{ $initial }}" @endif @required($required)>
@endfeature
