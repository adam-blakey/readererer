<?php

namespace App\Http\Controllers;

use App\Models\Ensemble;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeatingPlanController extends Controller
{
    public function show(Ensemble $ensemble)
    {
        $ensemble->load('users');

        $users = $ensemble->users->sortBy([
            ['pivot.seat_column', 'asc'],
            ['pivot.seat_row', 'asc'],
        ]);

        $groupedUsers = $users->groupBy(function ($user) {
            return $user->pivot->seat_row ?? 'unassigned';
        });

        return view('ensembles.seating-plan', [
            'ensemble' => $ensemble,
            'grouped_users' => $groupedUsers,
            'page_name' => 'Ensembles',
            'page_subname' => $ensemble->name . ' seating plan',
        ]);
    }

    public function update(Request $request, Ensemble $ensemble)
    {
        $seatingPlan = $request->input();

        DB::transaction(function () use ($seatingPlan, $ensemble) {
            foreach ($seatingPlan as $row => $users) {
                if ($row === 'unassigned') {
                    foreach ($users as $user) {
                        $ensemble->users()->updateExistingPivot($user['id'], [
                            'seat_row' => null,
                            'seat_column' => null,
                        ]);
                    }
                } else {
                    foreach ($users as $index => $user) {
                        $ensemble->users()->updateExistingPivot($user['id'], [
                            'seat_row' => $row,
                            'seat_column' => $user['column'],
                        ]);
                    }
                }
            }
        });

        return response()->json(['status' => 'success']);
    }
}
