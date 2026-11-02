@props(['entities', 'create_entity' => null, 'show_past_toggle' => false])

<x-layout>
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
                    <div class="d-flex align-items-center">
                        <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-2">
                            <input type="hidden" name="page" value="{{ request('page') }}" />
                            <label class="form-check m-4 pb-0">
                                <input class="form-check-input" type="checkbox" id="withTrashed" name="with_trashed" value="1" {{ request('with_trashed') ? 'checked' : '' }} onchange="this.form.submit()">
                                <span class="form-check-label">{{ __('Show archived') }}</span>
                            </label>
                            @if ($show_past_toggle)
                                <label class="form-check m-4 pb-0">
                                    <input class="form-check-input" type="checkbox" id="withPast" name="with_past" value="1" {{ request('with_past') ? 'checked' : '' }} onchange="this.form.submit()">
                                    <span class="form-check-label">{{ __('Show past terms') }}</span>
                                </label>
                            @endif
                        </form>
                        <div class="card-actions ms-auto me-4">
                            @if ($create_entity)
                                <x-a :route="$create_entity['route']" class="btn btn-primary">
                                    {{ __('Add :name', ['name' => $create_entity['name']]) }}
                                </x-a>
                            @endif
                        </div>
                    </div>
					<x-table :$entities />
				</x-card>
			</div>
		</x-card-row>
		{{ $entities->links() }}
	</div>
</x-layout>
