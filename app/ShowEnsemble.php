<?php

namespace App;

use App\Models\Ensemble;
use App\Models\Term;
use Illuminate\Support\Carbon;

trait ShowEnsemble
{
    public function show(Ensemble $ensemble)
    {
        $upcoming_terms = Term::where('latest_date', '>', Carbon::now())
            ->with('term_dates')
            ->get();
        $upcoming_terms = $upcoming_terms->sortBy('latest_date');

        $next_rehearsal = $upcoming_terms
            ->flatMap(fn ($term) => $term->term_dates)
            ->filter(fn ($term_date) => $term_date->ensemble_id === null)
            ->where('start_datetime', '>', Carbon::now())
            ->sortBy('start_datetime')
            ->first();

        $next_concert = $upcoming_terms
            ->flatMap(fn ($term) => $term->term_dates)
            ->filter(fn ($term_date) => (int)$term_date->ensemble_id === (int)$ensemble->id)
            ->where('start_datetime', '>', Carbon::now())
            ->sortBy('start_datetime')
            ->first();

        return view('ensembles.show', [
            'ensemble' => $ensemble,
            'upcomingTerms' => $upcoming_terms,
            'nextRehearsal' => $next_rehearsal,
            'nextConcert' => $next_concert,
            'page_name' => $ensemble->name
        ]);
    }
}
