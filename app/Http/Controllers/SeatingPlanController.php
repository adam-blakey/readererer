<?php

namespace App\Http\Controllers;

use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\TermDate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SeatingPlanController extends Controller
{
    public function show(Ensemble $ensemble)
    {
        $this->authorize('update', $ensemble);

        $ensemble->load('users');

        $users = $ensemble->users->sortBy([
            ['pivot.seat_column', 'asc'],
            ['pivot.seat_row', 'asc'],
        ]);

        $instrumentIds = $users->pluck('pivot.instrument_family_id')->filter()->unique();
        $instruments = InstrumentFamily::whereIn('id', $instrumentIds)->get()->keyBy('id');

        $users->each(function ($user) use ($instruments) {
            $instrument = $user->pivot->instrument_family_id
                ? $instruments->get($user->pivot->instrument_family_id)
                : null;
            $user->instrument_name = $instrument?->name;
            $user->instrument_color = $instrument?->color;
            $user->original_seat_row = $user->pivot->seat_row;
            $user->original_seat_column = $user->pivot->seat_column;
        });

        $groupedUsers = $users->groupBy(function ($user) {
            return $user->pivot->seat_row ?? 'unassigned';
        });

        $maxRow = $groupedUsers->keys()
            ->filter(fn ($key) => $key !== 'unassigned')
            ->max();

        if (! $maxRow) {
            $maxRow = 'A';
        }

        $nextRow = $maxRow;
        $nextRow++;
        $allRows = range('A', $nextRow);

        $finalGroupedUsers = new Collection;

        foreach ($allRows as $row) {
            $finalGroupedUsers->put($row, $groupedUsers->get($row, new Collection));
        }

        $unassignedUsers = $groupedUsers->get('unassigned', new Collection)
            ->sortBy('name')
            ->groupBy(fn ($user) => $user->instrument_name ?? 'No instrument')
            ->sortKeys();

        $termDatesForEnsemble = TermDate::with('concert_ensembles')->where(function ($query) use ($ensemble) {
            $query->whereDoesntHave('concert_ensembles')
                ->orWhereHas('concert_ensembles', fn ($query) => $query->whereKey($ensemble->id));
        });

        $upcomingTermDates = (clone $termDatesForEnsemble)
            ->where('start_datetime', '>=', now())
            ->orderBy('start_datetime')
            ->get();

        $pastTermDates = (clone $termDatesForEnsemble)
            ->where('start_datetime', '<', now())
            ->orderByDesc('start_datetime')
            ->get();

        return view('ensembles.seating-plan', [
            'ensemble' => $ensemble,
            'grouped_users' => $finalGroupedUsers,
            'unassigned_users' => $unassignedUsers,
            'upcoming_term_dates' => $upcomingTermDates,
            'past_term_dates' => $pastTermDates,
            'page_name' => __('Ensembles'),
            'page_subname' => __(':name seating plan', ['name' => $ensemble->name]),
        ]);
    }

    public function update(Request $request, Ensemble $ensemble)
    {
        $this->authorize('update', $ensemble);

        $input = $request->input();
        $seatingPlan = json_decode($input['seating_plan'], true);

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
                    foreach ($users as $user) {
                        $ensemble->users()->updateExistingPivot($user['id'], [
                            'seat_row' => $row,
                            'seat_column' => $user['column'],
                        ]);
                    }
                }
            }
        });

        return to_route('ensembles.seating-plan.show', $ensemble);
    }

    public function download(Ensemble $ensemble, TermDate $termDate)
    {
        $this->authorize('view', $ensemble);

        return $ensemble->seatingPlanPdf($termDate)->stream();
    }
}
