@props(['location'])

@if ($location?->map_url)
    <a href="{{ $location->map_url }}" rel="noopener" target="_blank" title="{{ __('Open in OpenStreetMap') }}">{{ $location->name }}</a>
@else
    {{ $location?->name ?? __('TBC') }}
@endif
