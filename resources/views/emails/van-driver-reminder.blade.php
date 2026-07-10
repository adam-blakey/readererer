<x-mail::message>
# Van driver reminder

Hi {{ $vanDriver->first_name ?? $vanDriver->name }},

This is a reminder that **you are down to drive the van** for:

**{{ $termDate->name }}**

@if($termDate->concert_ensemble)
This is a concert for **{{ $termDate->concert_ensemble->name }}**.
@else
This is a rehearsal.
@endif

@if($termDate->setup_group)
**{{ $termDate->setup_group->name }}** is on setup duty for this date.
@endif

Please make sure the van (and everything in it) arrives in good time.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
