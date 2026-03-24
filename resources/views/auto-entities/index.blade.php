@props(['entities', 'page_name', 'page_subname', 'create_entity' => null])

<x-layout :$page_name :$page_subname>
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
                    <div class="d-flex">
                        <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-2">
                            <input type="hidden" name="page" value="{{ request('page') }}" />
                            <label class="form-check m-4 pb-0">
                                <input class="form-check-input" type="checkbox" id="withTrashed" name="with_trashed" value="1" {{ request('with_trashed') ? 'checked' : '' }} onchange="this.form.submit()">
                                <span class="form-check-label">Show archived</span>
                            </label>
                        </form>
                        <!-- TODO: align right and fix weird alignment -->
                        <div class="card-actions">
                            @if ($create_entity)
                                <x-a :route="$create_entity['route']" class="btn btn-primary">
                                    Add {{ $create_entity['name'] }}
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
