<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\Term;
use App\Models\TermDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendances = Attendance::with(['user', 'edit_user', 'term_date'])
            ->whereRelation('user', 'deleted_at', null)
            ->whereRelation('edit_user', 'deleted_at', null)
            ->whereRelation('term_date', 'deleted_at', null)
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('attendances.index', [
            'attendances' => $attendances,
            'page_name' => __('Attendance updates'),
        ]);
    }

    public function poll(Ensemble $ensemble, Term $term, Request $request)
    {
        $this->authorize('view', $ensemble);

        $term_dates = $term->term_dates
            ->filter(fn (TermDate $term_date) => $term_date->appliesToEnsemble($ensemble))
            ->values()
            ->load(['concert_ensemble', 'setup_group.van_drivers', 'van_driver']);

        $members = $ensemble->users()
            ->wherePivotNotNull('instrument_family_id')
            ->with(['setup_group', 'attendances' => fn ($query) => $query->whereIn('term_date_id', $term_dates->pluck('id'))])
            ->get();

        return view('attendances.poll', [
            'members' => $members,
            'term' => $term,
            'term_dates' => $term_dates,
            'instrument_families' => InstrumentFamily::whereIn('id', $members->pluck('pivot.instrument_family_id'))->get()->keyBy('id'),
            'van_driver_ids' => $term_dates->mapWithKeys(fn (TermDate $term_date) => [$term_date->id => $term_date->inferred_van_driver?->id]),
            'page_name' => $ensemble->name.': '.$term->name,
            'ensemble' => $ensemble,
            'sortby' => $request->query('sortby') ?? 'first_name',
        ]);
    }

    public function poll_store(Ensemble $ensemble, Term $term, Request $request)
    {
        $this->authorize('view', $ensemble);

        $member_ids = $ensemble->users()->wherePivotNotNull('instrument_family_id')->pluck('users.id');
        $term_date_ids = $term->term_dates->filter(fn (TermDate $term_date) => $term_date->appliesToEnsemble($ensemble))->pluck('id');

        foreach ($request->except('_token') as $key => $value) {
            $status = is_numeric($value) ? AttendanceStatus::tryFrom((int) $value) : null;

            if ($status === null
                || ! preg_match('/^status-t(\d+)m(\d+)$/', $key, $ids)
                || ! $term_date_ids->contains((int) $ids[1])
                || ! $member_ids->contains((int) $ids[2])) {
                continue;
            }

            Attendance::create([
                'user_id' => (int) $ids[2],
                'term_date_id' => (int) $ids[1],
                'ensemble_id' => $ensemble->id,
                'status' => $status,
                'edit_user_id' => Auth::id(),
                'edit_ip' => $request->ip(),
            ]);
        }

        return redirect()->route('attendance.poll', ['ensemble' => $ensemble, 'term' => $term]);
    }

    public function create() {}

    public function store(StoreAttendanceRequest $request) {}

    public function show(Attendance $attendance) {}

    public function edit(Attendance $attendance) {}

    public function update(UpdateAttendanceRequest $request, Attendance $attendance) {}

    public function destroy(Attendance $attendance) {}
}
