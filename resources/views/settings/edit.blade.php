@props(['page_name', 'has_uploaded_logo'])

@php
	use App\Models\Setting;

	$terminology = config('app.readererer_terminology');
	$switches = [
		'assume_attending' => [__('Assume members are attending'), __('Members who have not replied to a poll count as attending.')],
		'allow_change_to_unknown' => [__('Allow changing a reply back to unknown'), __('Lets members clear their poll reply once they have given one.')],
		'repeating_headings' => [__('Repeat headings in polls'), __('Groups the poll by section, repeating the date headings above each group.')],
	];
@endphp

<x-layout>
	<div class="container-xl">
		<form action="{{ route('settings.update') }}" class="card" enctype="multipart/form-data" method="POST">
			@csrf
			@method('PUT')

			<div class="card-body">
				<x-forms.error-summary :fields="['name', 'logo', 'remove_logo', 'image_max_kilobytes', 'assume_attending', 'allow_change_to_unknown', 'repeating_headings', 'terminology', 'terminology.*']" />
				@if (session('status'))
					<div class="alert alert-success" role="alert">{{ session('status') }}</div>
				@endif

				<div class="row g-5">
					<div class="col-xl-6">
						<h2 class="mb-3">{{ __('Site') }}</h2>
						<div class="mb-3">
							<label class="form-label required" for="name">{{ __('Site name') }}</label>
							<input class="form-control @error('name') is-invalid @enderror" id="name" name="name" required type="text" value="{{ old('name', config('app.name')) }}">
							<div class="form-hint">{{ __('Shown in page titles and as the sender of emails.') }}</div>
							@error('name')
								<x-forms.input-error :messages="$message" />
							@enderror
						</div>
						<div class="mb-3">
							<label class="form-label" for="logo">{{ __('Logo') }}</label>
							<img alt="" class="mb-2 d-block" src="{{ logo_url() }}" style="max-height: 3rem">
							<input accept="image/jpeg,image/png,image/webp,image/gif" class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" type="file">
							<div class="form-hint">{{ __('JPEG, PNG, WebP or GIF, up to :size MB.', ['size' => round(config('app.readererer_image_max_kilobytes') / 1024, 1)]) }}</div>
							@error('logo')
								<x-forms.input-error :messages="$message" />
							@enderror
							@if ($has_uploaded_logo)
								<label class="mt-2 form-check">
									<input class="form-check-input" name="remove_logo" type="checkbox" value="1" @checked(old('remove_logo'))>
									<span class="form-check-label">{{ __('Remove the uploaded logo') }}</span>
								</label>
							@endif
						</div>
						<div class="mb-3">
							<label class="form-label required" for="image_max_kilobytes">{{ __('Largest image upload (KB)') }}</label>
							<input class="form-control @error('image_max_kilobytes') is-invalid @enderror" id="image_max_kilobytes" max="10240" min="1" name="image_max_kilobytes" required type="number" value="{{ old('image_max_kilobytes', config('app.readererer_image_max_kilobytes')) }}">
							@error('image_max_kilobytes')
								<x-forms.input-error :messages="$message" />
							@enderror
						</div>

						<h2 class="mt-4 mb-3">{{ __('Attendance polls') }}</h2>
						@foreach ($switches as $key => [$label, $hint])
							<div class="mb-3">
								<input name="{{ $key }}" type="hidden" value="0">
								<label class="form-check form-switch">
									<input class="form-check-input" name="{{ $key }}" type="checkbox" value="1" @checked(old($key, config('app.readererer_'.$key)))>
									<span class="form-check-label">{{ $label }}</span>
								</label>
								<div class="form-hint">{{ $hint }}</div>
							</div>
						@endforeach
					</div>
					<div class="col-xl-6">
						<h2 class="mb-1">{{ __('Wording') }}</h2>
						<p class="text-secondary">{{ __('Replace these words throughout the site. Leave a box empty to keep the original word, and use lower case unless the word is always capitalised.') }}</p>
						<div class="table-responsive">
							<table class="table table-vcenter">
								<thead>
									<tr>
										<th>{{ __('Word') }}</th>
										<th>{{ __('Singular') }}</th>
										<th>{{ __('Plural') }}</th>
									</tr>
								</thead>
								<tbody>
									@foreach (Setting::TERMS as $singular => $plural)
										<tr>
											<td>{{ $singular }} / {{ $plural }}</td>
											@foreach ([$singular, $plural] as $word)
												<td>
													<input aria-label="{{ $word }}" class="form-control @error('terminology.'.$word) is-invalid @enderror" maxlength="50" name="terminology[{{ $word }}]" placeholder="{{ $word }}" type="text" value="{{ old('terminology.'.$word, $terminology[$word] ?? '') }}">
													@error('terminology.'.$word)
														<x-forms.input-error :messages="$message" />
													@enderror
												</td>
											@endforeach
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			<div class="card-footer text-end">
				<button class="btn btn-primary" type="submit">{{ __('Save settings') }}</button>
			</div>
		</form>
	</div>
</x-layout>
