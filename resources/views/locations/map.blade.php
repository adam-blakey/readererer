{{-- A static map built from OpenStreetMap's tiles, which the CSP and cross-origin isolation allow where an embedded map frame would not. --}}
@php
    $zoom = 16;
    $height = 400;
    $position = $location->tilePosition($zoom);
@endphp

<div class="mt-3">
    <x-card-row>
        <div class="col-md-12">
            <x-card>
                <div class="card-header">
                    <h3 class="card-title">{{ __('Map') }}</h3>
                    @if ($location->map_url)
                        <div class="card-actions">
                            <a href="{{ $location->map_url }}" rel="noopener" target="_blank"><x-icon name="external-link" /> {{ __('Open in OpenStreetMap') }}</a>
                        </div>
                    @endif
                </div>
                @if ($position)
                    @php
                        [$x, $y] = [$position[0] * 256, $position[1] * 256];
                    @endphp
                    <div class="position-relative overflow-hidden" role="img" aria-label="{{ __('Map of :name', ['name' => $location->name]) }}" style="height: {{ $height }}px">
                        {{-- Enough tiles to fill a 2048px-wide card around the pin. --}}
                        @for ($row = intdiv((int) $y - $height / 2, 256); $row <= intdiv((int) $y + $height / 2, 256); $row++)
                            @for ($column = intdiv((int) $x - 1024, 256); $column <= intdiv((int) $x + 1024, 256); $column++)
                                <img alt="" class="position-absolute" height="256" loading="lazy" src="{{ App\Http\Middleware\AddSecurityHeaders::allowImage("https://tile.openstreetmap.org/{$zoom}/{$column}/{$row}.png") }}" style="left: calc(50% + {{ round($column * 256 - $x) }}px); top: calc(50% + {{ round($row * 256 - $y) }}px)" width="256">
                            @endfor
                        @endfor
                        <span class="position-absolute text-red" style="left: 50%; top: 50%; transform: translate(-50%, -100%) scale(1.75); transform-origin: bottom center"><x-icon name="map-pin" /></span>
                        <a class="position-absolute bottom-0 end-0 bg-white bg-opacity-75 px-1 small" href="https://www.openstreetmap.org/copyright" rel="noopener" target="_blank">© {{ __('OpenStreetMap contributors') }}</a>
                    </div>
                @else
                    <div class="card-body text-secondary">{{ __('No coordinates yet. Add an address to look them up, or enter them yourself.') }}</div>
                @endif
            </x-card>
        </div>
    </x-card-row>
</div>
