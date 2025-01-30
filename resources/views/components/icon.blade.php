@props(['icon', 'color' => 'currentColor'])

@switch($icon)
	@case('google')
		<svg class="icon icon-tabler icons-tabler-filled icon-tabler-brand-google" fill="{{ $color }}" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none" />
			<path d="M12 2a9.96 9.96 0 0 1 6.29 2.226a1 1 0 0 1 .04 1.52l-1.51 1.362a1 1 0 0 1 -1.265 .06a6 6 0 1 0 2.103 6.836l.001 -.004h-3.66a1 1 0 0 1 -.992 -.883l-.007 -.117v-2a1 1 0 0 1 1 -1h6.945a1 1 0 0 1 .994 .89c.04 .367 .061 .737 .061 1.11c0 5.523 -4.477 10 -10 10s-10 -4.477 -10 -10s4.477 -10 10 -10z" />
		</svg>
	@break

	@case('home')
		<svg class="icon icon-tabler icons-tabler-outline icon-tabler-home" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none" />
			<path d="M5 12l-2 0l9 -9l9 9l-2 0" />
			<path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
			<path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
		</svg>
	@break

	@case('file-description')
		<svg class="icon icon-tabler icons-tabler-outline icon-tabler-file-description" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none" />
			<path d="M14 3v4a1 1 0 0 0 1 1h4" />
			<path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
			<path d="M9 17h6" />
			<path d="M9 13h6" />
		</svg>
	@break

	@case('music')
		<svg class="icon icon-tabler icons-tabler-outline icon-tabler-music" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none" />
			<path d="M3 17a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
			<path d="M13 17a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
			<path d="M9 17v-13h10v13" />
			<path d="M9 8h10" />
		</svg>
	@break

	@case('calendar-month')
		<svg class="icon icon-tabler icons-tabler-filled icon-tabler-calendar-month" fill="currentColor" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none" />
			<path d="M8 12a1 1 0 0 1 1 1v4a1 1 0 0 1 -2 0v-4a1 1 0 0 1 1 -1" />
			<path d="M12 12a1 1 0 0 1 1 1v4a1 1 0 0 1 -2 0v-4a1 1 0 0 1 1 -1" />
			<path d="M16 12a1 1 0 0 1 1 1v4a1 1 0 0 1 -2 0v-4a1 1 0 0 1 1 -1" />
			<path d="M16 2a1 1 0 0 1 .993 .883l.007 .117v1h1a3 3 0 0 1 2.995 2.824l.005 .176v12a3 3 0 0 1 -2.824 2.995l-.176 .005h-12a3 3 0 0 1 -2.995 -2.824l-.005 -.176v-12a3 3 0 0 1 2.824 -2.995l.176 -.005h1v-1a1 1 0 0 1 1.993 -.117l.007 .117v1h6v-1a1 1 0 0 1 1 -1m3 7h-14v9.625c0 .705 .386 1.286 .883 1.366l.117 .009h12c.513 0 .936 -.53 .993 -1.215l.007 -.16z" />
		</svg>
	@break

	@case('square-check')
		<svg class="icon icon-tabler icons-tabler-outline icon-tabler-square-check" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none" />
			<path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
			<path d="M9 12l2 2l4 -4" />
		</svg>
	@break

	@case('user')
		<svg class="icon icon-tabler icons-tabler-outline icon-tabler-user" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none" />
			<path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
			<path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
		</svg>
	@break

	@case('eye')
		<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none"></path>
			<path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
			<path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path>
		</svg>
	@break

	@case('eye-closed')
		<svg class="icon icon-tabler icons-tabler-outline icon-tabler-eye-closed" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none" />
			<path d="M21 9c-2.4 2.667 -5.4 4 -9 4c-3.6 0 -6.6 -1.333 -9 -4" />
			<path d="M3 15l2.5 -3.8" />
			<path d="M21 14.976l-2.492 -3.776" />
			<path d="M9 17l.5 -4" />
			<path d="M15 17l-.5 -4" />
		</svg>
	@break

	@case('login')
		<svg class="icon icon-tabler icons-tabler-outline icon-tabler-login" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none" />
			<path d="M15 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" />
			<path d="M21 12h-13l3 -3" />
			<path d="M11 15l-3 -3" />
		</svg>
	@break

	@case('mail')
		<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none"></path>
			<path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"></path>
			<path d="M3 7l9 6l9 -6"></path>
		</svg>
	@break

	@case('dashboard')
		<svg class="icon icon-tabler icons-tabler-outline icon-tabler-layout-dashboard" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none" />
			<path d="M5 4h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-6a1 1 0 0 1 1 -1" />
			<path d="M5 16h4a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-2a1 1 0 0 1 1 -1" />
			<path d="M15 12h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-6a1 1 0 0 1 1 -1" />
			<path d="M15 4h4a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-2a1 1 0 0 1 1 -1" />
		</svg>
	@break

	@case('refresh')
		<svg class="icon icon-tabler icons-tabler-outline icon-tabler-refresh" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none" />
			<path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
			<path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
		</svg>
	@break

	@case('pencil')
		<svg class="icon icon-tabler icons-tabler-outline icon-tabler-pencil" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none" />
			<path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
			<path d="M13.5 6.5l4 4" />
		</svg>
	@break

	@case('user')
		<svg class="icon icon-tabler icons-tabler-outline icon-tabler-user" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none" />
			<path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
			<path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
		</svg>
	@break

	@case('mail')
		<svg class="icon icon-tabler icons-tabler-outline icon-tabler-mail" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
			<path d="M0 0h24v24H0z" fill="none" stroke="none" />
			<path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" />
			<path d="M3 7l9 6l9 -6" />
		</svg>
	@break

	@default
@endswitch
