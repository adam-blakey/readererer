<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Models\Ensemble;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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
            'page_name' => 'Attendance updates'
        ]);
    }

    /**
     * The members of an ensemble who play in it (i.e. have an instrument family).
     */
    private function playing_members(Ensemble $ensemble)
    {
        return User::latest()
            ->with('attendances')
            ->with('ensembles')
            ->with('setup_group')
            ->get()
            ->filter(function($user) use ($ensemble) { return $user->ensembles->contains($ensemble) && $user->ensembles->where('id', $ensemble->id)->first()->pivot->instrument_family_id != null; })
            ->values();
    }

    /**
     * Display the read-only attendance register for an ensemble's term.
     */
    public function show(Ensemble $ensemble, Term $term)
    {
        // Ensure the current user is allowed to view this ensemble (restrict ensemble users to their own ensemble)
        $this->authorize('view', $ensemble);

        $members = $this->playing_members($ensemble)
            ->sortBy('first_name')
            ->values();

        return view('attendances.show', [
            'members' => $members,
            'term' => $term,
            'page_name' => $ensemble->name . ': ' . $term->name,
            'ensemble' => $ensemble,
        ]);
    }

    /**
     * Show the attendance poll for editing.
     */
    public function edit(Ensemble $ensemble, Term $term, Request $request)
    {
        // Ensure the current user is allowed to view this ensemble (restrict ensemble users to their own ensemble)
        $this->authorize('view', $ensemble);

        $members = $this->playing_members($ensemble);

        $page_name = $ensemble->name . ': ' . $term->name;

        return view('attendances.edit', [
            'members' => $members,
            'term' => $term,
            'page_name' => $page_name,
            'ensemble' => $ensemble,
            'sortby' => $request->query('sortby') ?? 'first_name',
        ]);
    }

    /**
     * Record the submitted poll statuses. Attendance history is append-only,
     * so each submission creates new records rather than mutating old ones.
     */
    public function update(Ensemble $ensemble, Term $term, Request $request)
    {
        // Ensure the current user is allowed to view this ensemble (restrict ensemble users to their own ensemble)
        $this->authorize('view', $ensemble);

        $request_ip = $request->ip();

        $request->collect()->each(function($parameter_value, $parameter_key) use ($request_ip, $ensemble) {
            if (in_array($parameter_key, ['_token', '_method'])) {
                return;
            }

            assert(substr($parameter_key, 0, 7) == 'status-');

            $data = preg_split('/(t|m)/', explode('-', $parameter_key)[1], -1, PREG_SPLIT_NO_EMPTY);

            assert(count($data) == 2);
            $term_date_id = $data[0];
            $member_id = $data[1];

            Attendance::create([
                'user_id' => $member_id,
                'term_date_id' => $term_date_id,
                'ensemble_id' => $ensemble->id,
                'status' => $parameter_value,
                'edit_user_id' => Auth::user()->id,
                'edit_ip' => $request_ip
            ]);
        });

        return redirect()->route('attendance.show', ['ensemble' => $ensemble, 'term' => $term]);
    }
}
