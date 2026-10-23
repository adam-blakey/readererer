@props(['model', 'label' => null, 'hint' => null])

@php
	$label ??= __('Image');
	$current = $model->image_url;
	$max_megabytes = round(config('app.readererer_image_max_kilobytes') / 1024, 1);
@endphp

<div class="mb-3">
	<label class="form-label" for="image">{{ $label }}</label>
	<div class="d-flex gap-3 align-items-center">
		<span class="rounded avatar avatar-lg" @if ($current) style="background-image: url({{ $current }})" @endif></span>
		<div class="flex-fill">
			<input accept="image/jpeg,image/png,image/webp,image/gif" class="form-control @error('image') is-invalid @enderror" id="image" name="image" type="file">
			@error('image')
				<x-forms.input-error :messages="$message" />
			@enderror
			<div class="form-hint">{{ $hint ?? __('JPEG, PNG, WebP or GIF, up to :size MB.', ['size' => $max_megabytes]) }}</div>
		</div>
	</div>
	@if ($current)
		<label class="mt-2 form-check">
			<input class="form-check-input" name="remove_image" type="checkbox" value="1" @checked(old('remove_image'))>
			<span class="form-check-label">{{ __('Remove the current image') }}</span>
		</label>
	@endif
</div>
