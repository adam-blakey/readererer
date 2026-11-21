@props(['term_dates', 'ensembles', 'split' => false, 'past' => false])

<x-layout>
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					@if ($split)
						<div class="card-header">
							<div class="card-actions">
								<x-a class="btn" href="{{ route('attendance.register.index', $past ? [] : ['past' => 1]) }}">
									<x-icon name="{{ $past ? 'calendar-event' : 'history' }}" />
									{{ $past ? __('View upcoming dates') : __('View past dates') }}
								</x-a>
							</div>
						</div>
					@endif
					<x-attendances.registers-table :$ensembles :$term_dates />
				</x-card>
			</div>
		</x-card-row>
		<x-per-page-select :paginator="$term_dates" />
		{{ $term_dates->links() }}
	</div>
</x-layout>
