<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\RegisterStatus;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\RegisterEntry;
use App\Models\TermDate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * The attendance register: who actually turned up to a given rehearsal or
 * concert, as opposed to the poll, which records who said they would.
 */
class AttendanceRegisterController extends Controller
{
    /**
     * List the dates a register can be taken for, most recent first.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', RegisterEntry::class);

        $term_dates = TermDate::with(['term', 'concert_ensemble', 'register_entries'])
            ->orderByDesc('start_datetime')
            ->paginate(15);

        $ensembles = Ensemble::orderBy('name')
            ->get()
            ->filter(fn (Ensemble $ensemble) => Gate::allows('view', $ensemble))
            ->values();

        return view('attendances.registers', [
            'term_dates' => $term_dates,
            'ensembles' => $ensembles,
            'page_name' => 'Attendance registers',
        ]);
    }

    /**
     * Show the register for one ensemble on one date.
     */
    public function show(Ensemble $ensemble, TermDate $termDate, Request $request)
    {
        $this->authorize('viewAny', RegisterEntry::class);
        $this->authorize('view', $ensemble);

        abort_unless($termDate->appliesToEnsemble($ensemble), 404);

        $sortby = $request->query('sortby') ?? 'first_name';
        $members = $this->sorted_members($ensemble, $sortby);

        return view('attendances.register', [
            'ensemble' => $ensemble,
            'term_date' => $termDate,
            'members' => $members,
            'entries' => $this->entries($ensemble, $termDate),
            'polled_statuses' => $this->polled_statuses($ensemble, $termDate),
            'instrument_families' => $this->instrument_families($ensemble),
            'sortby' => $sortby,
            'page_name' => $ensemble->name.': '.$termDate->date_label,
        ]);
    }

    /**
     * Record the register for one ensemble on one date.
     */
    public function store(Ensemble $ensemble, TermDate $termDate, Request $request)
    {
        $this->authorize('create', RegisterEntry::class);
        $this->authorize('view', $ensemble);

        abort_unless($termDate->appliesToEnsemble($ensemble), 404);

        $validated = $request->validate([
            'status' => ['nullable', 'array'],
            'status.*' => ['integer', Rule::in(array_map(fn (RegisterStatus $status) => $status->value, RegisterStatus::cases()))],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:255'],
        ]);

        $statuses = $validated['status'] ?? [];
        $notes = $validated['notes'] ?? [];
        $member_ids = $this->members($ensemble)->pluck('id');

        foreach ($statuses as $user_id => $status) {
            $user_id = (int) $user_id;

            // Ignore anything submitted for someone who isn't on this register.
            if (! $member_ids->contains($user_id)) {
                continue;
            }

            $status = RegisterStatus::from((int) $status);
            $note = trim($notes[$user_id] ?? '') ?: null;

            $keys = [
                'user_id' => $user_id,
                'term_date_id' => $termDate->id,
                'ensemble_id' => $ensemble->id,
            ];

            // A member who is neither marked nor annotated leaves no record
            // behind, so an accidental entry can be undone.
            if ($status === RegisterStatus::Unmarked && $note === null) {
                RegisterEntry::where($keys)->delete();

                continue;
            }

            RegisterEntry::updateOrCreate($keys, [
                'status' => $status,
                'notes' => $note,
                'marked_by_user_id' => Auth::id(),
            ]);
        }

        return redirect()
            ->route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $termDate, 'sortby' => $request->query('sortby')])
            ->with('status', 'Register saved.');
    }

    /**
     * The members a register covers: everyone in the ensemble who plays
     * something. Members without an instrument family are still setting up
     * their membership, exactly as on the poll.
     */
    private function members(Ensemble $ensemble): Collection
    {
        return $ensemble->users
            ->filter(fn ($user) => $user->pivot->instrument_family_id !== null)
            ->values();
    }

    private function sorted_members(Ensemble $ensemble, string $sortby): Collection
    {
        $members = $this->members($ensemble);

        if ($sortby === 'instrument_family') {
            $instrument_families = $this->instrument_families($ensemble);

            return $members
                ->sortBy(fn ($user) => [
                    $instrument_families->get($user->pivot->instrument_family_id)?->name ?? '',
                    $user->first_name,
                ])
                ->values();
        }

        if ($sortby === 'last_name') {
            return $members->sortBy('last_name')->values();
        }

        return $members->sortBy('first_name')->values();
    }

    /**
     * The instrument families played in this ensemble, keyed by id.
     */
    private function instrument_families(Ensemble $ensemble): Collection
    {
        $ids = $ensemble->users->pluck('pivot.instrument_family_id')->filter()->unique();

        return InstrumentFamily::whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * The register as it currently stands, keyed by user id.
     */
    private function entries(Ensemble $ensemble, TermDate $termDate): Collection
    {
        return RegisterEntry::where('ensemble_id', $ensemble->id)
            ->where('term_date_id', $termDate->id)
            ->get()
            ->keyBy('user_id');
    }

    /**
     * What each member last said on the poll for this date, keyed by user id,
     * so whoever takes the register can see who was expected.
     */
    private function polled_statuses(Ensemble $ensemble, TermDate $termDate): Collection
    {
        return Attendance::where('ensemble_id', $ensemble->id)
            ->where('term_date_id', $termDate->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Attendance $attendance) => [
                $attendance->user_id => $attendance->status ?? AttendanceStatus::Unknown,
            ]);
    }
}
