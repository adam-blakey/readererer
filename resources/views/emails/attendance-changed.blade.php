<x-mail::message>
# {{ __('Attendance changed') }}

{{ __('These replies for :ensemble changed after the attendance list was sent for', ['ensemble' => $ensemble->name]) }} **{{ $termDate->name }}**:

@foreach($changes as $change)
- **{{ $change['name'] }}:** {{ __(':to (was :from)', ['to' => $change['to'], 'from' => $change['from']]) }}
@endforeach

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
