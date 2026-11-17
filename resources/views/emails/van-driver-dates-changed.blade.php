<x-mail::message>
# {{ __('Your van driving dates have changed') }}

{{ __('Hi :name,', ['name' => $vanDriver?->first_name ?? $vanDriver?->name]) }}

@if($termDates->isEmpty())
{{ __('The van driver rota has changed, and you now have no upcoming dates to drive the van.') }}
@else
{{ __('The van driver rota has changed. You are next down to drive the van for:') }}

@foreach($termDates as $termDate)
- **{{ $termDate->name }}** ({{ $termDate->location_label }})
@endforeach
@endif

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
