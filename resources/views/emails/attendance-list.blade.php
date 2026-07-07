<x-mail::message>
# Attendance list

Here is the current attendance for **{{ $termDate->name }}**.

@php($totals = member_status_totals($members, $termDate))
- **Attending:** {{ $totals['attending'] }}
- **Not attending:** {{ $totals['not_attending'] }}
@isset($totals['unknown'])
- **Not yet responded:** {{ $totals['unknown'] }}
@endisset

@if($members->isNotEmpty())
<x-mail::table>
| Member | Status |
| :----- | :----- |
@foreach($members as $member)
@php($attendance = $member->attendances->where('term_date_id', $termDate->id)->sortByDesc('created_at')->first())
| {{ $member->name }} | {{ $attendance?->status_text ?? 'No response yet' }} |
@endforeach
</x-mail::table>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
