<x-mail::message>
# Setup reminder

This is a reminder that **{{ $setupGroup->name }}** is on setup duty for:

**{{ $termDate->name }}**

@if($termDate->concert_ensemble)
This is a concert for **{{ $termDate->concert_ensemble->name }}**.
@else
This is a rehearsal.
@endif

@if($termDate->inferred_van_driver)
The van driver for this date is **{{ $termDate->inferred_van_driver->name }}**.
@endif

Please make sure you arrive in good time to set up.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
