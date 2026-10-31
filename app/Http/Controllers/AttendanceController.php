<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\User;
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

        $members = User::latest()
            ->with('attendances')
            ->with('ensembles')
            ->with('setup_group')
            ->get()
            ->filter(function ($user) use ($ensemble) {
                return $user->ensembles->contains($ensemble) && $user->ensembles->where('id', $ensemble->id)->first()->pivot->instrument_family_id != null;
            })
            ->values();

        $page_name = $ensemble->name.': '.$term->name;

        return view('attendances.poll', [
            'members' => $members,
            'term' => $term,
            'page_name' => $page_name,
            'ensemble' => $ensemble,
            'sortby' => $request->query('sortby') ?? 'first_name',
        ]);
    }

    public function poll_store(Ensemble $ensemble, Term $term, Request $request)
    {
        $this->authorize('view', $ensemble);

        $request_ip = $request->ip();

        $request->collect()->each(function ($parameter_value, $parameter_key) use ($request_ip, $ensemble) {
            if ($parameter_key == '_token') {
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
                'edit_ip' => $request_ip,
            ]);
        });

        return redirect()->route('attendance.poll', ['ensemble' => $ensemble, 'term' => $term]);
    }

    public function create() {}

    public function store(StoreAttendanceRequest $request) {}

    public function show(Attendance $attendance) {}

    public function edit(Attendance $attendance) {}

    public function update(UpdateAttendanceRequest $request, Attendance $attendance) {}

    public function destroy(Attendance $attendance) {}
}
