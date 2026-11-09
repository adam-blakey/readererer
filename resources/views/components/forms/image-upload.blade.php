@props(['model', 'label'])

@php
	$max_megabytes = round(config('app.readererer_image_max_kilobytes') / 1024, 1);
@endphp

<div class="mb-3">
	<label class="form-label" for="image">{{ $label }}</label>
	<div class="d-flex gap-3 align-items-center">
		<span class="rounded avatar avatar-lg" style="background-image: url({{ $model->image_url }})"></span>
		<div class="flex-fill">
			<input @feature(\App\Features\ImageScaling::class) data-scale-image @endfeature accept="image/jpeg,image/png,image/webp" class="form-control @error('image') is-invalid @enderror" id="image" name="image" type="file">
			@error('image')
				<x-forms.input-error :messages="$message" />
			@enderror
			<div class="form-hint">{{ __('JPEG, PNG or WebP, up to :size MB.', ['size' => $max_megabytes]) }}</div>
		</div>
	</div>
	@if (filled($model->image))
		<label class="mt-2 form-check">
			<input class="form-check-input" name="remove_image" type="checkbox" value="1" @checked(old('remove_image'))>
			<span class="form-check-label">{{ __('Remove the current image') }}</span>
		</label>
	@endif
</div>
