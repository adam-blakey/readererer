@props(['attendances'])

<x-layout>
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					@if ($attendances->isEmpty())
						<p class="p-5">{{ __('Never updated.') }}</p>
					@else
						<div class="table-responsive">
							<table class="table table-vcenter card-table">
								<thead>
									<tr>
										<th>{{ __('Term date') }}</th>
										<th>{{ __('Status') }}</th>
										<th>{{ __('Editing user') }}</th>
										<th>{{ __('Updated at') }}</th>
									</tr>
								</thead>
								<tbody>
									@foreach ($attendances as $attendance)
										<tr>
											<td>{{ $attendance->term_date->date_label }}</td>
											<td>{{ $attendance->status->label() }}@if ($attendance->dep_name): {{ $attendance->dep_name }}@endif</td>
											<td>{{ $attendance->edit_user?->name ?? '-' }}</td>
											<td>{{ $attendance->created_at->format('D, j M Y, H:i') }}</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					@endif
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>
