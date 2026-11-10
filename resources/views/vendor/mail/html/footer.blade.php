<tr>
	<td class="py-xl" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-top: 48px; padding-bottom: 48px;">
		<table cellpadding="0" cellspacing="0" class="text-center font-sm text-muted" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; border-collapse: collapse; width: 100%; color: #9eb0b7; text-align: center; font-size: 13px;">
@php($social_links = array_filter(array_intersect_key(config('app.readererer_social_links') ?? [], App\Settings\GeneralSettings::SOCIAL_NETWORKS)))
@if ($social_links)
			<tr>
				<td align="center" class="pb-md" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-bottom: 16px;">
					<table cellpadding="0" cellspacing="0" class="w-auto" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; border-collapse: collapse; width: auto;">
						<tr>
							@foreach ($social_links as $network => $url)
								<td class="px-sm" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-right: 8px; padding-left: 8px;">
									<a href="{{ $url }}" style="color: #467fcf; text-decoration: none;">
										<img alt="{{ App\Settings\GeneralSettings::SOCIAL_NETWORKS[$network][0] }}" class=" va-middle" height="24" src="{{ Vite::asset('resources/images/tabler/icons-gray-social-'.App\Settings\GeneralSettings::SOCIAL_NETWORKS[$network][1].'.png') }}" style="line-height: 100%; outline: none; text-decoration: none; vertical-align: middle; font-size: 0; border: 0 none;" width="24" />
									</a>
								</td>
							@endforeach
						</tr>
					</table>
				</td>
			</tr>
@endif
			<tr>
				<td class="px-lg" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-right: 24px; padding-left: 24px;">
					{!! __('If you have any questions, feel free to message us at :email.', ['email' => '<a class="text-muted" href="mailto:'.e(config('mail.from.address')).'" style="color: #9eb0b7; text-decoration: none;">'.e(config('mail.from.address')).'</a>']) !!}
				</td>
			</tr>
			<tr>
				<td class="pt-md" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-top: 16px;">
					{!! __('You are receiving this email because you have an account with :app.', ['app' => '<a class="text-muted" href="'.e(config('app.url')).'" style="color: #9eb0b7; text-decoration: none;">'.e(config('app.name')).'</a>']) !!}
				</td>
			</tr>
			<tr>
				<td class="pt-md" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-top: 16px;">
					© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
				</td>
			</tr>
		</table>
	</td>
</tr>
