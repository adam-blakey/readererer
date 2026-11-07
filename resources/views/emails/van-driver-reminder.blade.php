<x-mail::message>
# {{ __('Van driver reminder') }}

{{ __('Hi :name,', ['name' => $vanDriver->first_name ?? $vanDriver->name]) }}

{{ __('This is a reminder that') }} **{{ __('you are down to drive the van') }}** {{ __('for') }}:

**{{ $termDate->name }}**

{{ __('Location: :location', ['location' => $termDate->location_label]) }}
@if($termDate->inferred_location?->map_url)
([{{ __('Open in OpenStreetMap') }}]({{ $termDate->inferred_location->map_url }}))
@endif

@if($termDate->is_concert)
{{ __('This is a concert for') }} **{{ $termDate->concert_ensemble_names }}**.
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
