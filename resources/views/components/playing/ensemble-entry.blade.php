@props(['ensemble', 'totals', 'is_yours' => false])

<div class="py-2 d-flex align-items-center border-bottom">
	<span class="rounded avatar" style="background-image: url({{ $ensemble->image }})"></span>
	<div class="ps-2">
		<div>
			{{ $ensemble->name }}
			@if ($is_yours)
				<span class="badge bg-blue text-blue-fg ms-1">Yours</span>
			@endif
		</div>
		<div class="mt-1">
			<x-playing.summary :$totals />
		</div>
	</div>
</div>
