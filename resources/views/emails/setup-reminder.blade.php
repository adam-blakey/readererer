<x-mail::message>
# {{ __('Setup reminder') }}

{{ __('This is a reminder that') }} **{{ $setupGroup->name }}** {{ __('is on setup duty for') }}:

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

@if($termDate->inferred_van_driver)
{{ __('The van driver for this date is') }} **{{ $termDate->inferred_van_driver->name }}**.
@endif

{{ __('Please make sure you arrive in good time to set up.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
