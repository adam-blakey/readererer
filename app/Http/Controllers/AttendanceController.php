<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Models\Ensemble;
use App\Models\Term;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $attendances = Attendance::latest()
        ->with(['user', 'edit_user', 'term_date'])
        ->orderBy('created_at', 'DESC')
        ->paginate(10);

        return view('attendances.index', [
            'attendances' => $attendances,
            'page_name' => 'Attendance updates'
        ]);
    }

    /**
     * Display the attendance poll.
     */
    public function poll(Ensemble $ensemble, Term $term)
    {
        $members = User::latest()
        ->with('attendances')
        ->with('ensembles')
        ->get()
        ->filter(function($user) use ($ensemble) { return $user->ensembles->contains($ensemble); })
        ->sortBy('start_datetime')
        ->values();

        $page_name = $ensemble->name . ': ' . $term->name;

        return view('attendances.poll', [
            'members' => $members,
            'term' => $term,
            'page_name' => $page_name,
            'ensemble' => $ensemble
        ]);
    }

    public function poll_store(Ensemble $ensemble, Term $term, Request $request)
    {
        $request_ip = $request->ip();

        $request->collect()->each(function($parameter_value, $parameter_key) use ($request_ip, $ensemble) {
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
                'edit_ip' => $request_ip
            ]);
        });

        return redirect()->route('attendance.poll', ['ensemble' => $ensemble, 'term' => $term]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAttendanceRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Attendance $attendance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attendance $attendance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendance)
    {
        //
    }
}