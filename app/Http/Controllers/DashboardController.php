<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\TermDate;
use App\Traits\ShowEnsemble;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use ShowEnsemble;

    public function index()
    {
        $user = Auth::user();

        if ($user->role == UserRole::Ensemble) {
            return $this->show($user->ensembles()->first());
        }

        $ensembles = $user->ensembles()->get();
        $setupGroup = $user->setup_group;

        // Upcoming rehearsal (any non-concert term date)
        $nextRehearsal = null;
        if (!empty($ensembles)) {
            $nextRehearsal = TermDate::whereNull('concert_ensemble_id')
                ->where('start_datetime', '>', Carbon::now())
                ->orderBy('start_datetime')
                ->first();
        }

        // Upcoming concerts for user's ensembles
        $ensembleIds = $ensembles->pluck('id');
        $nextConcerts = TermDate::whereNotNull('concert_ensemble_id')
            ->whereIn('concert_ensemble_id', $ensembleIds)
            ->where('start_datetime', '>', Carbon::now())
            ->orderBy('start_datetime')
            ->limit(3)
            ->get();

        // Next time the user will be driving the van
        $nextVanDrive = null;
        if ($setupGroup) {
            $upcomingWithGroup = TermDate::where('setup_group_id', $setupGroup->id)
                ->where('start_datetime', '>', Carbon::now())
                ->orderBy('start_datetime')
                ->get();

            $nextVanDrive = $upcomingWithGroup->first(function ($td) use ($user) {
                // Use the accessor that infers from rotation if explicit driver not set
                return optional($td->inferred_van_driver)->id === $user->id;
            });
        }

        return view('dashboard.index', [
            'page_name' => config('app.name') . ' dashboard',
            'ensembles' => $ensembles,
            'setupGroup' => $setupGroup,
            'nextRehearsal' => $nextRehearsal,
            'nextConcerts' => $nextConcerts,
            'nextVanDrive' => $nextVanDrive,
            'playingWith' => $this->playingWith(collect([$nextRehearsal])->concat($nextConcerts), $ensembleIds),
        ]);
    }

    /**
     * The ensembles the user will be playing with at each of the given dates,
     * keyed by term date id: just the concert ensemble for a concert, or
     * every ensemble for a shared rehearsal. Each entry carries that
     * ensemble's attendance totals and whether the user belongs to it.
     * Ensembles with no members are left out — you cannot play with nobody.
     */
    private function playingWith(Collection $termDates, Collection $ensembleIds): Collection
    {
        $termDates = $termDates->filter();

        if ($termDates->isEmpty()) {
            return collect();
        }

        // Every ensemble that could be playing, loaded once and then picked
        // over per date rather than queried for again and again.
        $allEnsembles = Ensemble::with('users.attendances')->orderBy('name')->get();

        return $termDates->mapWithKeys(fn (TermDate $termDate) => [
            $termDate->id => $termDate->playing_ensembles($allEnsembles)
                ->filter(fn (Ensemble $ensemble) => $ensemble->users->isNotEmpty())
                ->map(fn (Ensemble $ensemble) => [
                    'ensemble' => $ensemble,
                    'totals' => member_status_totals($ensemble->users, $termDate),
                    'is_yours' => $ensembleIds->contains($ensemble->id),
                ])
                ->values(),
        ]);
    }
}
