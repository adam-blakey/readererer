@props(['page_name', 'has_uploaded_logo', 'has_google_client_secret'])

@php
	use App\Enums\UserRole;
	use App\Models\Setting;

	$terminology = config('app.readererer_terminology');
	$switch_groups = [
		__('Attendance polls') => [
			'assume_attending' => [__('Assume members are attending'), __('Members who have not replied to a poll count as attending.')],
			'allow_change_to_unknown' => [__('Allow changing a reply back to unknown'), __('Lets members clear their poll reply once they have given one.')],
			'repeating_headings' => [__('Repeat headings in polls'), __('Groups the poll by section, repeating the date headings above each group.')],
		],
		__('Features') => [
			'setup_groups' => [__('Use setup groups'), __('Shows setup groups, their badges and setup reminders. Turning this off hides them but keeps their data.')],
			'van_drivers' => [__('Use van drivers'), __('Shows van drivers and van driver reminders. Turning this off hides them but keeps their data.')],
			'single_ensemble' => [__('Single ensemble'), __('For a site with one ensemble: new users join it when they are created. Needs a site with at most one ensemble, and stops more being added.')],
		],
		__('Email') => [
			'log_emails' => [__('Log emails instead of sending them'), __('Writes emails to storage/logs/emails.log rather than sending them. Use this on test sites so members are not emailed.')],
		],
	];
	$user_fields = [
		'phone_number' => __('Phone number'),
		'address' => __('Address'),
		'emergency_contact' => __('Emergency contact details'),
		'date_of_birth' => __('Date of birth'),
		'has_photo_permission' => __('Photograph permission'),
		'is_gift_aiding_subs' => __('Gift aid subs'),
	];
	$shown_user_fields = old('shown_user_fields', config('app.readererer_shown_user_fields'));
	$sections = array_filter([
		['ensembles.index', 'music', __('Ensembles')],
		['instrumentfamilys.index', 'guitar-pick', __('Instrument families')],
		['terms.index', 'calendar-month', __('Terms')],
		config('app.readererer_setup_groups') ? ['setupgroups.index', 'users', __('Setup groups')] : null,
	]);
	$can_edit = Gate::allows('edit.settings');
	$after_reminders = [
		'nothing' => __('Nothing'),
		'follow_up' => __('Email the admins about each change'),
		'lock' => __('Lock the poll for members'),
	];
@endphp

<x-layout>
	<div class="container-xl">
		<div class="card mb-3">
			<div class="card-body btn-list">
				@foreach ($sections as [$route, $icon, $title])
					<a class="btn" href="{{ route($route) }}"><x-icon :name="$icon" /> {{ $title }}</a>
				@endforeach
			</div>
		</div>

		<form action="{{ route('settings.update') }}" class="card" enctype="multipart/form-data" method="POST">
			@csrf
			@method('PUT')

			<fieldset class="card-body" @disabled(! $can_edit)>
				<x-forms.error-summary :fields="['name', 'logo', 'remove_logo', 'image_max_kilobytes', 'assume_attending', 'allow_change_to_unknown', 'repeating_headings', 'setup_groups', 'van_drivers', 'single_ensemble', 'reminder_hours', 'after_reminders', 'log_emails', 'terminology', 'terminology.*', 'shown_user_fields', 'shown_user_fields.*', 'google_login', 'google_client_id', 'google_client_secret', 'google_register_domain', 'google_register_role']" />
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

						@foreach ($switch_groups as $heading => $switches)
							<h2 class="mt-4 mb-3">{{ $heading }}</h2>
							@foreach ($switches as $key => [$label, $hint])
								<div class="mb-3">
									<input name="{{ $key }}" type="hidden" value="0">
									<label class="form-check form-switch">
										<input @class(['form-check-input', 'is-invalid' => $errors->has($key)]) name="{{ $key }}" type="checkbox" value="1" @checked(old($key, config('app.readererer_'.$key)))>
										<span class="form-check-label">{{ $label }}</span>
									</label>
									<div class="form-hint">{{ $hint }}</div>
									@error($key)
										<x-forms.input-error :messages="$message" />
									@enderror
								</div>
							@endforeach
						@endforeach

						<h2 class="mt-4 mb-3">{{ __('Reminders') }}</h2>
						<div class="mb-3">
							<label class="form-label" for="reminder_hours">{{ __('Hours before a date to send reminders') }}</label>
							<input class="form-control @error('reminder_hours') is-invalid @enderror" id="reminder_hours" max="720" min="1" name="reminder_hours" type="number" value="{{ old('reminder_hours', config('app.readererer_reminder_hours') ?: '') }}">
							<div class="form-hint">{{ __('Sends the attendance list to the ensemble admins, and the setup and van driver reminders, this long before each date. Leave empty to send them only by hand.') }}</div>
							@error('reminder_hours')
								<x-forms.input-error :messages="$message" />
							@enderror
						</div>
						<div class="mb-3">
							<label class="form-label required" for="after_reminders">{{ __('After reminders are sent') }}</label>
							<select class="form-select @error('after_reminders') is-invalid @enderror" id="after_reminders" name="after_reminders" required>
								@foreach ($after_reminders as $value => $label)
									<option value="{{ $value }}" @selected(old('after_reminders', config('app.readererer_after_reminders')) === $value)>{{ $label }}</option>
								@endforeach
							</select>
							<div class="form-hint">{{ __('What happens when a reply changes after the reminder time. Moderators and admins can always change replies.') }}</div>
							@error('after_reminders')
								<x-forms.input-error :messages="$message" />
							@enderror
						</div>

						<h2 class="mt-4 mb-1">{{ __('Member details') }}</h2>
						<p class="text-secondary">{{ __('Ticked details are shown on member pages and forms. Unticking one hides it but keeps the details already saved.') }}</p>
						@foreach ($user_fields as $key => $label)
							<label class="form-check">
								<input class="form-check-input" name="shown_user_fields[]" type="checkbox" value="{{ $key }}" @checked(in_array($key, $shown_user_fields))>
								<span class="form-check-label">{{ $label }}</span>
							</label>
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

						<h2 class="mt-4 mb-1">{{ __('Login with Google') }}</h2>
						<p class="text-secondary">{{ __('Lets members log in with the Google account that has their email address. Create an OAuth client in the Google Cloud console and add :url as an authorised redirect URI.', ['url' => route('google.callback')]) }}</p>
						<div class="mb-3">
							<input name="google_login" type="hidden" value="0">
							<label class="form-check form-switch">
								<input @class(['form-check-input', 'is-invalid' => $errors->has('google_login')]) name="google_login" type="checkbox" value="1" @checked(old('google_login', config('app.readererer_google_login')))>
								<span class="form-check-label">{{ __('Allow login with Google') }}</span>
							</label>
						</div>
						<div class="mb-3">
							<label class="form-label" for="google_client_id">{{ __('Client ID') }}</label>
							<input class="form-control @error('google_client_id') is-invalid @enderror" id="google_client_id" maxlength="255" name="google_client_id" type="text" value="{{ old('google_client_id', config('services.google.client_id')) }}">
							@error('google_client_id')
								<x-forms.input-error :messages="$message" />
							@enderror
						</div>
						<div class="mb-3">
							<label class="form-label" for="google_client_secret">{{ __('Client secret') }}</label>
							<input autocomplete="off" class="form-control @error('google_client_secret') is-invalid @enderror" id="google_client_secret" maxlength="255" name="google_client_secret" type="password">
							<div class="form-hint">{{ $has_google_client_secret ? __('A secret is saved. Leave this empty to keep it.') : __('Stored encrypted.') }}</div>
							@error('google_client_secret')
								<x-forms.input-error :messages="$message" />
							@enderror
						</div>
						<div class="mb-3">
							<label class="form-label" for="google_register_domain">{{ __('Register new users from this domain') }}</label>
							<div class="input-group">
								<span class="input-group-text">@</span>
								<input class="form-control @error('google_register_domain') is-invalid @enderror" id="google_register_domain" maxlength="255" name="google_register_domain" placeholder="example.org" type="text" value="{{ old('google_register_domain', config('app.readererer_google_register_domain')) }}">
							</div>
							<div class="form-hint">{{ __('Someone logging in with a Google account on this domain who has no account yet gets one with the role below. Leave empty to only let existing users log in.') }}</div>
							@error('google_register_domain')
								<x-forms.input-error :messages="$message" />
							@enderror
						</div>
						<div class="mb-3">
							<label class="form-label" for="google_register_role">{{ __('Role for new users') }}</label>
							<select class="form-select @error('google_register_role') is-invalid @enderror" id="google_register_role" name="google_register_role">
								<option value="">{{ __('None') }}</option>
								@foreach (UserRole::cases() as $role)
									@continue($role === UserRole::Ensemble)
									<option value="{{ $role->value }}" @selected((string) old('google_register_role', config('app.readererer_google_register_role')) === (string) $role->value)>{{ $role->label() }}</option>
								@endforeach
							</select>
							@error('google_register_role')
								<x-forms.input-error :messages="$message" />
							@enderror
						</div>
					</div>
				</div>
			</fieldset>
			@if ($can_edit)
				<div class="card-footer d-flex justify-content-between">
					<a class="btn" href="{{ route('settings.import') }}">{{ __('Import from Pollererer') }}</a>
					<button class="btn btn-primary" type="submit">{{ __('Save settings') }}</button>
				</div>
			@endif
		</form>
	</div>
</x-layout>
