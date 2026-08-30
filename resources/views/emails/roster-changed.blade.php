<x-mail::message>
# {{ __('Setup group / van driver changed') }}

{{ __('The setup arrangements for the following date have changed:') }}

**{{ $termDate->name }}**

@if($termDate->concert_ensemble)
{{ __('This is a concert for') }} **{{ $termDate->concert_ensemble->name }}**.
@else
{{ __('This is a rehearsal.') }}
@endif

@foreach($changes as $change)
- {{ $change }}
@endforeach

{{ __('If this affects you, please double-check your plans for this date.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
