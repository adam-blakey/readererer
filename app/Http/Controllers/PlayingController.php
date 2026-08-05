<?php

namespace App\Http\Controllers;

use App\Models\Ensemble;
use App\Models\TermDate;
use Illuminate\Support\Facades\Auth;

class PlayingController extends Controller
{
    /**
     * The upcoming rehearsals and concerts the signed-in user plays at, and
     * the ensembles they will be playing alongside at each of them.
     */
    public function index()
    {
        $user = Auth::user();
        $own_ensemble_ids = $user->ensembles->pluck('id');

        $term_dates = TermDate::upcoming()
            ->forUser($user)
            ->with('concert_ensemble')
            ->limit(10)
            ->get();

        // Every ensemble that could be playing, loaded once and then picked
        // over per date rather than queried for again and again.
        $all_ensembles = Ensemble::with('users.attendances')->orderBy('name')->get();

        $ensembles = $term_dates->mapWithKeys(fn (TermDate $term_date) => [
            $term_date->id => $term_date->playing_ensembles($all_ensembles)
                ->filter(fn (Ensemble $ensemble) => $ensemble->users->isNotEmpty())
                ->map(fn (Ensemble $ensemble) => [
                    'ensemble' => $ensemble,
                    'totals' => member_status_totals($ensemble->users, $term_date),
                    'is_yours' => $own_ensemble_ids->contains($ensemble->id),
                ])
                ->values(),
        ]);

        return view('playing.index', [
            'term_dates' => $term_dates,
            'ensembles' => $ensembles,
            // The apostrophe is a typographic one on purpose: a page name
            // passes through two Blade component hops (view -> layout -> page
            // header) and each one HTML-escapes a plain "'" again, so it would
            // end up on the page as "&#039;".
            'page_name' => 'Who you’re playing with',
            'page_subname' => 'Upcoming rehearsals and concerts',
        ]);
    }
}
