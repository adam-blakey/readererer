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
                @if ($location->map_embed_url)
                    <iframe class="w-100 d-block border-0" height="400" loading="lazy" src="{{ $location->map_embed_url }}" title="{{ __('Map of :name', ['name' => $location->name]) }}"></iframe>
                @else
                    <div class="card-body text-secondary">{{ __('No coordinates yet. Add an address to look them up, or enter them yourself.') }}</div>
                @endif
            </x-card>
        </div>
    </x-card-row>
</div>
