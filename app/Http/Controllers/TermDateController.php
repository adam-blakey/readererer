<?php

namespace App\Http\Controllers;

use App\Features\TermDateOverview;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\TermDate;
use Laravel\Pennant\Feature;

class TermDateController extends Controller
{
    public function show(TermDate $termDate)
    {
        abort_unless(Feature::active(TermDateOverview::class), 404);

        $termDate->load(['term', 'concert_ensembles', 'setup_group.members', 'setup_group.van_drivers', 'email_logs.recipients']);

        $ensembles = Ensemble::orderBy('name')
            ->with(['users' => fn ($query) => $query->orderBy('first_name')->orderBy('last_name'), 'users.attendances' => fn ($query) => $query->where('term_date_id', $termDate->id)->with('edit_user')])
            ->get()
            ->filter(fn (Ensemble $ensemble) => $termDate->appliesToEnsemble($ensemble))
            ->values();

        return view('term-dates.show', [
            'term_date' => $termDate,
            'ensembles' => $ensembles,
            'last_updated' => Attendance::where('term_date_id', $termDate->id)->latest()->first()?->created_at,
            'page_name' => $termDate->schedule_label,
        ]);
    }
}
