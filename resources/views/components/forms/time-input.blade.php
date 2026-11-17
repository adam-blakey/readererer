@props(['name', 'value' => null, 'id' => null, 'required' => false, 'invalid' => false, 'initial' => null])

@php
    $twenty_four_hour = config('app.readererer_twenty_four_hour_clock');
@endphp

<input {{ $attributes->class(['form-control', 'is-invalid' => $invalid]) }} id="{{ $id }}" maxlength="{{ $twenty_four_hour ? 5 : 10 }}" name="{{ $name }}" size="{{ $twenty_four_hour ? 5 : 7 }}" pattern="{{ $twenty_four_hour ? '([01][0-9]|2[0-3]):[0-5][0-9]' : ' *(0?[1-9]|1[0-2]):[0-5][0-9] *[AaPp]\.?[Mm]\.? *' }}" placeholder="{{ $twenty_four_hour ? 'HH:MM' : 'h:mm am' }}" title="{{ $twenty_four_hour ? __('A 24-hour time, such as 19:30') : __('A 12-hour time, such as 7:30pm') }}" type="text" value="{{ display_time($value) }}" @if ($initial !== null) data-initial="{{ display_time($initial) }}" @endif @required($required)>
