<tr>
	<td class="py-xl" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-top: 48px; padding-bottom: 48px;">
		<table cellpadding="0" cellspacing="0" class="text-center font-sm text-muted" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; border-collapse: collapse; width: 100%; color: #9eb0b7; text-align: center; font-size: 13px;">
			<tr>
				<td align="center" class="pb-md" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-bottom: 16px;">
					<table cellpadding="0" cellspacing="0" class="w-auto" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; border-collapse: collapse; width: auto;">
						<tr>
							<td class="px-sm" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-right: 8px; padding-left: 8px;">
								<a href="https://tabler.io/emails?utm_source=demo" style="color: #467fcf; text-decoration: none;">
									<img alt="social-facebook-square" class=" va-middle" height="24" src="{{ Vite::asset('resources/images/tabler/icons-gray-social-facebook-square.png') }}" style="line-height: 100%; outline: none; text-decoration: none; vertical-align: middle; font-size: 0; border: 0 none;" width="24" />
								</a>
							</td>
							<td class="px-sm" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-right: 8px; padding-left: 8px;">
								<a href="https://tabler.io/emails?utm_source=demo" style="color: #467fcf; text-decoration: none;">
									<img alt="social-twitter" class=" va-middle" height="24" src="{{ Vite::asset('resources/images/tabler/icons-gray-social-twitter.png') }}" style="line-height: 100%; outline: none; text-decoration: none; vertical-align: middle; font-size: 0; border: 0 none;" width="24" />
								</a>
							</td>
							<td class="px-sm" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-right: 8px; padding-left: 8px;">
								<a href="https://tabler.io/emails?utm_source=demo" style="color: #467fcf; text-decoration: none;">
									<img alt="social-github" class=" va-middle" height="24" src="{{ Vite::asset('resources/images/tabler/icons-gray-social-github.png') }}" style="line-height: 100%; outline: none; text-decoration: none; vertical-align: middle; font-size: 0; border: 0 none;" width="24" />
								</a>
							</td>
							<td class="px-sm" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-right: 8px; padding-left: 8px;">
								<a href="https://tabler.io/emails?utm_source=demo" style="color: #467fcf; text-decoration: none;">
									<img alt="social-youtube" class=" va-middle" height="24" src="{{ Vite::asset('resources/images/tabler/icons-gray-social-youtube.png') }}" style="line-height: 100%; outline: none; text-decoration: none; vertical-align: middle; font-size: 0; border: 0 none;" width="24" />
								</a>
							</td>
							<td class="px-sm" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-right: 8px; padding-left: 8px;">
								<a href="https://tabler.io/emails?utm_source=demo" style="color: #467fcf; text-decoration: none;">
									<img alt="social-pinterest" class=" va-middle" height="24" src="{{ Vite::asset('resources/images/tabler/icons-gray-social-pinterest.png') }}" style="line-height: 100%; outline: none; text-decoration: none; vertical-align: middle; font-size: 0; border: 0 none;" width="24" />
								</a>
							</td>
							<td class="px-sm" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-right: 8px; padding-left: 8px;">
								<a href="https://tabler.io/emails?utm_source=demo" style="color: #467fcf; text-decoration: none;">
									<img alt="social-instagram" class=" va-middle" height="24" src="{{ Vite::asset('resources/images/tabler/icons-gray-social-instagram.png') }}" style="line-height: 100%; outline: none; text-decoration: none; vertical-align: middle; font-size: 0; border: 0 none;" width="24" />
								</a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="px-lg" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-right: 24px; padding-left: 24px;">
					If you have any questions, feel free to message us at <a class="text-muted" href="mailto:{{ config('mail.from.address') }}" style="color: #9eb0b7; text-decoration: none;">{{ config('mail.from.address') }}.</a>
				</td>
			</tr>
			<tr>
				<td class="pt-md" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-top: 16px;">
					You are receiving this email because you have an account with {{ config('app.name') }}.
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
