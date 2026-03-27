<?php

namespace App\Http\Controllers;

use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class SeatingPlanController extends Controller
{
    public function show(Ensemble $ensemble)
    {
        $ensemble->load('users');

        $users = $ensemble->users->sortBy([
            ['pivot.seat_column', 'asc'],
            ['pivot.seat_row', 'asc'],
        ]);

        $instrumentIds = $users->pluck('pivot.instrument_family_id')->filter()->unique();
        $instruments = InstrumentFamily::whereIn('id', $instrumentIds)->pluck('name', 'id');

        $users->each(function ($user) use ($instruments) {
            if ($user->pivot->instrument_family_id) {
                $user->instrument_name = $instruments->get($user->pivot->instrument_family_id);
            } else {
                $user->instrument_name = null;
            }
            $user->original_seat_row = $user->pivot->seat_row;
            $user->original_seat_column = $user->pivot->seat_column;
        });

        $groupedUsers = $users->groupBy(function ($user) {
            return $user->pivot->seat_row ?? 'unassigned';
        });

        $maxRow = $groupedUsers->keys()
            ->filter(fn ($key) => $key !== 'unassigned')
            ->max();

        if (!$maxRow) {
            $maxRow = 'A';
        }

        $nextRow = $maxRow;
        $nextRow++;
        $allRows = range('A', $nextRow);

        $finalGroupedUsers = new Collection();
        $finalGroupedUsers->put('unassigned', $groupedUsers->get('unassigned', new Collection()));

        foreach ($allRows as $row) {
            $finalGroupedUsers->put($row, $groupedUsers->get($row, new Collection()));
        }

        return view('ensembles.seating-plan', [
            'ensemble' => $ensemble,
            'grouped_users' => $finalGroupedUsers,
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
