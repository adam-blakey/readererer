<x-mail::message>
# {{ __('Attendance list') }}

{{ __('Here is the current attendance for') }} **{{ $termDate->name }}**.

@php($totals = member_status_totals($members, $termDate))
- **{{ __('Attending') }}:** {{ $totals['attending'] }}
- **{{ __('Not attending') }}:** {{ $totals['not_attending'] }}
@isset($totals['unknown'])
- **{{ __('Not yet responded') }}:** {{ $totals['unknown'] }}
@endisset

@if($members->isNotEmpty())
<x-mail::table>
| {{ __('Member') }} | {{ __('Status') }} |
| :----- | :----- |
@foreach($members as $member)
@php($attendance = $member->attendances->where('term_date_id', $termDate->id)->sortByDesc('created_at')->first())
| {{ $member->name }} | {{ $attendance?->status_text ?? __('No response yet') }} |
@endforeach
</x-mail::table>
@endif

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
