@props(['term_dates', 'ensembles', 'split' => false, 'past' => false])

<x-layout>
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					@if ($split)
						<div class="card-header">
							<ul class="nav nav-tabs card-header-tabs">
								@foreach ([__('Upcoming') => [false, [], 'calendar-event'], __('Past') => [true, ['past' => 1], 'history']] as $label => [$is_past, $query, $icon])
									<li class="nav-item">
										<a @class(['nav-link', 'active' => $past === $is_past]) href="{{ route('attendance.register.index', $query) }}" @if ($past === $is_past) aria-current="page" @endif>
											<span class="me-2 d-inline-flex"><x-icon name="{{ $icon }}" /></span>
											{{ $label }}
										</a>
									</li>
								@endforeach
							</ul>
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
