@props(['url'])
<tr>
	<td class="header">
		<a href="{{ $url }}" style="display: inline-block;">
			@if (trim($slot) === 'Laravel')
				<img alt="Laravel Logo" class="logo" src="https://laravel.com/img/notification-logo.png">
			@else
				{{ $slot }}
			@endif
		</a>
	</td>
</tr>
