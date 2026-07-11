<?php

namespace App\Http\Controllers;

use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\TermDate;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
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

        $termDatesForEnsemble = TermDate::where(function ($query) use ($ensemble) {
            $query->whereNull('concert_ensemble_id')
                ->orWhere('concert_ensemble_id', $ensemble->id);
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
            'page_name' => 'Ensembles',
            'page_subname' => $ensemble->name.' seating plan',
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

        $members = User::latest()
            ->with('attendances')
            ->with('ensembles')
            ->with('setup_group')
            ->get()
            ->filter(function ($user) use ($ensemble) {
                return $user->ensembles->contains($ensemble) && $user->ensembles->where('id', $ensemble->id)->first()->pivot->instrument_family_id != null;
            })
            ->values();

        $instrumentFamilyIds = $members
            ->map(fn ($member) => $member->ensembles->where('id', $ensemble->id)->first()->pivot->instrument_family_id)
            ->unique();
        $instrumentFamilies = InstrumentFamily::whereIn('id', $instrumentFamilyIds)->orderBy('name')->get();

        Pdf::setOption(['debugCss' => true]);
        $pdf = Pdf::loadView('mail.seating-plan-pdf', compact('ensemble', 'termDate', 'members', 'instrumentFamilies'))->setPaper('a4', 'landscape');

        return $pdf->stream();

        // return view('mail.seating-plan-pdf', compact('ensemble', 'termDate', 'members'));
    }
}
