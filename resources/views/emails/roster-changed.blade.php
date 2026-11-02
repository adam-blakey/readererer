<x-mail::message>
# {{ __('Setup group / van driver changed') }}

{{ __('The setup arrangements for the following date have changed:') }}

**{{ $termDate->name }}**

@if($termDate->is_concert)
{{ __('This is a concert for') }} **{{ $termDate->concert_ensemble_names }}**.
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
