@use('App\Enums\AttendanceStatus')
@php
$totals = member_status_totals($members, $termDate);
$total = array_sum($totals);
$replies = $members->sortBy('name')->map(fn ($member) => ['member' => $member, 'attendance' => $attendance = $member->latestAttendanceFor($termDate), 'status' => ($attendance?->status ?? AttendanceStatus::Unknown)->effective()]);
$deps = $replies->where('status', AttendanceStatus::Depping)->count();
$stats = array_filter([
	'attending' => [__('Attending'), '#5eba00', 'icons-green-check.png'],
	'not_attending' => [__('Not attending'), '#cd201f', 'icons-red-x.png'],
	'unknown' => [__('Not yet responded'), '#9aa0ac', 'icons-gray-question-mark.png'],
], fn ($key) => isset($totals[$key]), ARRAY_FILTER_USE_KEY);
$sections = [
	'attending' => $replies->whereIn('status', [AttendanceStatus::Attending, AttendanceStatus::Depping]),
	'not_attending' => $replies->where('status', AttendanceStatus::NotAttending),
	'unknown' => $replies->where('status', AttendanceStatus::Unknown),
];
$font = 'font-family: Open Sans, -apple-system, BlinkMacSystemFont, Roboto, Helvetica Neue, Helvetica, Arial, sans-serif;';
@endphp
<x-mail::message>
# {{ __('Attendance list') }}

{{ __('Here is the current attendance for') }} **{{ $termDate->name }}**.

<p style="{{ $font }} color: #9eb0b7; font-size: 13px;">{{ __('Sent :time', ['time' => $sentAt->format('l, jS F Y, H:i')]) }}</p>

@if($deps > 0)
**{{ trans_choice(':count member is sending a dep.|:count members are sending a dep.', $deps) }}**

@endif
<table cellpadding="0" cellspacing="0" style="{{ $font }} border-collapse: collapse; width: 100%; margin: 24px 0 8px;">
<tr>
@foreach($stats as $key => [$label, $colour])
@if($totals[$key] > 0)
<td bgcolor="{{ $colour }}" style="height: 8px; font-size: 0; line-height: 8px;" width="{{ round($totals[$key] / $total * 100, 1) }}%">&nbsp;</td>
@endif
@endforeach
@if($total === 0)
<td bgcolor="#f0f0f0" style="height: 8px; font-size: 0; line-height: 8px;" width="100%">&nbsp;</td>
@endif
</tr>
</table>
<table cellpadding="0" cellspacing="0" style="{{ $font }} border-collapse: collapse; width: 100%; table-layout: fixed;">
<tr>
@foreach($stats as $key => [$label, $colour])
<td align="left" style="{{ $font }} font-size: 13px; padding: 4px 8px 0 0; text-align: left;" valign="top"><span style="color: {{ $colour }}; font-size: 16px;">&#9632;</span> {{ $label }} <br /><strong style="font-size: 20px;">{{ $totals[$key] }}</strong> <span style="color: #9eb0b7;">{{ $total ? '('.round($totals[$key] / $total * 100).'%)' : '' }}</span></td>
@endforeach
</tr>
</table>
@foreach($stats as $key => [$label, $colour, $icon])
@continue($sections[$key]->isEmpty())
<h2 style="{{ $font }} font-weight: 300; font-size: 24px; line-height: 130%; border-bottom: 1px solid #f0f0f0; margin: 40px 0 .5em; text-align: left;">{{ $label }} ({{ $sections[$key]->count() }})</h2>
<table cellpadding="0" cellspacing="0" style="{{ $font }} border-collapse: collapse; width: 100%; margin-bottom: 24px;">
@foreach($sections[$key] as ['member' => $member, 'attendance' => $attendance, 'status' => $status])
<tr>
<td style="{{ $font }} padding: 0 16px 8px 0; width: 18px;" valign="middle"><img alt="{{ $label }}" height="18" src="{{ Vite::asset('resources/images/tabler/'.$icon) }}" style="line-height: 100%; outline: none; text-decoration: none; vertical-align: middle; font-size: 0; border: 0 none; max-width: none;" width="18" /></td>
<td align="left" style="{{ $font }} padding: 0 0 8px; text-align: left;">@if($status === AttendanceStatus::Depping)<s style="color: #9eb0b7;">{{ $member->name }}</s> <span>{{ $attendance->dep_name ? __('depped by :name', ['name' => $attendance->dep_name]) : __('Sending a dep') }}</span>@else{{ $member->name }}@endif</td>
</tr>
@endforeach
</table>
@endforeach

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
