<x-mail::message>
# {{ __('Van driver reminder') }}

{{ __('Hi :name,', ['name' => $vanDriver->first_name ?? $vanDriver->name]) }}

{{ __('This is a reminder that') }} **{{ __('you are down to drive the van') }}** {{ __('for') }}:

**{{ $termDate->name }}**

@if($termDate->concert_ensemble)
{{ __('This is a concert for') }} **{{ $termDate->concert_ensemble->name }}**.
@else
{{ __('This is a rehearsal.') }}
@endif

@if($termDate->setup_group)
**{{ $termDate->setup_group->name }}** {{ __('is on setup duty for this date.') }}
@endif

{{ __('Please make sure the van (and everything in it) arrives in good time.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
