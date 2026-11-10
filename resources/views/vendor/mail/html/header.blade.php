@props(['url'])
<tr>
	<td class="py-lg" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; padding-top: 24px; padding-bottom: 24px;">
		<table cellpadding="0" cellspacing="0" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif; border-collapse: collapse; width: 100%;">
			<tr>
				<td style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif;">
					<a href="{{ $url }}" style="color: #467fcf; text-decoration: none;"><img alt="{{ config('app.name') }}" height="34" src="{{ logo_url() }}" style="line-height: 100%; outline: none; text-decoration: none; vertical-align: baseline; font-size: 0; border: 0 none;" width="116" /></a>
				</td>
				@isset($viewOnlineUrl)
					<td align="right" class="text-right" style="font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif;">
						<a class="text-muted-light font-sm" href="{{ $viewOnlineUrl }}" style="color: #bbc8cd; text-decoration: none; font-size: 13px;">
							{{ __('View online') }}
						</a>
					</td>
				@endisset
			</tr>
		</table>
	</td>
</tr>
