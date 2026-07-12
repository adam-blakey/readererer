<x-mail::message>
# Setup group / van driver changed

The setup arrangements for the following date have changed:

**{{ $termDate->name }}**

@if($termDate->concert_ensemble)
This is a concert for **{{ $termDate->concert_ensemble->name }}**.
@else
This is a rehearsal.
@endif

@foreach($changes as $change)
- {{ $change }}
@endforeach

If this affects you, please double-check your plans for this date.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
