<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Mail\AttendanceChangedMail;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;
use App\Services\EmailDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendances = Attendance::latestUpdates()->paginate(10);

        return view('attendances.index', [
            'attendances' => $attendances,
            'page_name' => __('Attendance updates'),
        ]);
    }

    public function poll(Ensemble $ensemble, Term $term, Request $request)
    {
        $this->authorize('view', $ensemble);

        $term_dates = $term->term_dates
            ->load('concert_ensembles')
            ->filter(fn (TermDate $term_date) => $term_date->appliesToEnsemble($ensemble))
            ->values()
            ->load(['setup_group.van_drivers', 'van_driver']);

        $members = $ensemble->users()
            ->wherePivotNotNull('instrument_family_id')
            ->with(['setup_group', 'attendances' => fn ($query) => $query->whereIn('term_date_id', $term_dates->pluck('id'))])
            ->get();

        return view('attendances.poll', [
            'members' => $members,
            'term' => $term,
            'term_dates' => $term_dates,
            'instrument_families' => InstrumentFamily::whereIn('id', $members->pluck('pivot.instrument_family_id'))->get()->keyBy('id'),
            'van_driver_ids' => config('app.readererer_van_drivers')
                ? $term_dates->mapWithKeys(fn (TermDate $term_date) => [$term_date->id => $term_date->inferred_van_driver?->id])
                : collect(),
            'page_name' => $ensemble->name.': '.$term->name,
            'ensemble' => $ensemble,
            'sortby' => $request->query('sortby') ?? 'first_name',
        ]);
    }

    public function poll_view(Request $request)
    {
        $request->user()->putSetting('poll_own_view', $request->boolean('own'));

        return redirect()->back();
    }

    public function history(Ensemble $ensemble, Term $term, User $user)
    {
        $attendances = Attendance::with(['term_date', 'edit_user' => fn ($query) => $query->withTrashed()])
            ->where('user_id', $user->id)
            ->where('ensemble_id', $ensemble->id)
            ->whereRelation('term_date', 'term_id', $term->id)
            ->latest()
            ->latest('id')
            ->get();

        return view('attendances.history', [
            'attendances' => $attendances,
            'page_name' => __(':name: attendance history', ['name' => $user->name]),
        ]);
    }

    public function poll_store(Ensemble $ensemble, Term $term, Request $request, EmailDispatcher $dispatcher)
    {
        $this->authorize('view', $ensemble);

        $members = $ensemble->users()->wherePivotNotNull('instrument_family_id')->get()->keyBy('id');
        $term_dates = $term->term_dates->load('concert_ensembles')->filter(fn (TermDate $term_date) => $term_date->appliesToEnsemble($ensemble))->keyBy('id');
        $changes = [];

        foreach ($request->except('_token') as $key => $value) {
            $status = is_numeric($value) ? AttendanceStatus::tryFrom((int) $value) : null;

            if ($status === null
                || ! preg_match('/^status-t(\d+)m(\d+)$/', $key, $ids)
                || ! $term_dates->has((int) $ids[1])
                || ! $members->has((int) $ids[2])
                || $term_dates[(int) $ids[1]]->isLockedFor($request->user())) {
                continue;
            }

            $term_date = $term_dates[(int) $ids[1]];
            $member = $members[(int) $ids[2]];

            if ($this->sendsFollowUps($term_date)) {
                $previous = $this->effectiveStatus(Attendance::where([
                    'user_id' => $member->id,
                    'term_date_id' => $term_date->id,
                    'ensemble_id' => $ensemble->id,
                ])->latest()->latest('id')->value('status'));

                if ($previous !== $this->effectiveStatus($status)) {
                    $changes[$term_date->id][] = ['name' => $member->name, 'from' => $previous->label(), 'to' => $status->label()];
                }
            }

            Attendance::create([
                'user_id' => $member->id,
                'term_date_id' => $term_date->id,
                'ensemble_id' => $ensemble->id,
                'status' => $status,
                'edit_user_id' => Auth::id(),
                'edit_ip' => $request->ip(),
            ]);
        }

        foreach ($changes as $term_date_id => $term_date_changes) {
            $term_date = $term_dates[$term_date_id];
            $recipients = $term_date->attendanceListRecipients();

            if ($recipients->isNotEmpty()) {
                $dispatcher->send($term_date, $recipients, fn () => new AttendanceChangedMail($term_date, $ensemble, $term_date_changes));
            }
        }

        return redirect()->route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $term]);
    }

    private function sendsFollowUps(TermDate $term_date): bool
    {
        return config('app.readererer_after_reminders') === 'follow_up'
            && $term_date->isPastReminderTime()
            && $term_date->start_datetime->isFuture();
    }

    private function effectiveStatus(?AttendanceStatus $status): AttendanceStatus
    {
        $status ??= AttendanceStatus::Unknown;

        return $status === AttendanceStatus::Unknown && config('app.readererer_assume_attending') ? AttendanceStatus::Attending : $status;
    }
}
