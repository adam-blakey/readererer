@props(['paginator'])

@feature(\App\Features\TableSize::class)
	@php
		$sizes = collect(TABLE_SIZES)->push($paginator->perPage())->unique()->sort()->values();
		$carried = collect(explode('&', Arr::query(request()->except(['per_page', 'page']))))->filter()->map(fn ($pair) => array_map('urldecode', explode('=', $pair, 2)));
	@endphp
	<form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center justify-content-end gap-2 my-2">
		@foreach ($carried as [$name, $value])
			<input type="hidden" name="{{ $name }}" value="{{ $value }}">
		@endforeach
		<label for="perPage" class="form-label mb-0">{{ __('Rows per page') }}</label>
		<select id="perPage" name="per_page" class="form-select w-auto" data-submit-on-change>
			@foreach ($sizes as $size)
				<option value="{{ $size }}" @selected($size === $paginator->perPage())>{{ $size }}</option>
			@endforeach
		</select>
	</form>
@endfeature
