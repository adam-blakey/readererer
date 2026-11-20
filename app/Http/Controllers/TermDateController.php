<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\TermDate;
use Illuminate\Support\Facades\Gate;

class TermDateController extends Controller
{
    public function show(TermDate $termDate)
    {
        $termDate->load(['term', 'concert_ensembles', 'dress_code', 'setup_group.members', 'setup_group.van_drivers', 'email_logs.recipients']);
        $show_attendance = Gate::allows('viewAny', TermDate::class);

        $ensembles = ! $show_attendance ? collect() : Ensemble::shown()->orderBy('name')
            ->with(['users' => fn ($query) => $query->orderBy('first_name')->orderBy('last_name'), 'users.attendances' => fn ($query) => $query->where('term_date_id', $termDate->id)->with('edit_user')])
            ->get()
            ->filter(fn (Ensemble $ensemble) => $termDate->appliesToEnsemble($ensemble))
            ->values();

        return view('term-dates.show', [
            'term_date' => $termDate,
            'ensembles' => $ensembles,
            'show_attendance' => $show_attendance,
            'last_updated' => $show_attendance ? Attendance::where('term_date_id', $termDate->id)->latest()->first()?->created_at : null,
            'page_name' => $termDate->schedule_label,
        ]);
    }
}
