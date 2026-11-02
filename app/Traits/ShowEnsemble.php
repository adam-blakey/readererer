<?php

namespace App\Traits;

use App\Models\Ensemble;
use App\Models\Term;
use Illuminate\Support\Carbon;

trait ShowEnsemble
{
    public function show(Ensemble $ensemble)
    {
        $upcoming_terms = Term::current()
            ->with('term_dates.concert_ensembles')
            ->get()
            ->sortBy('latest_date');

        $next_rehearsal = $upcoming_terms
            ->flatMap(fn ($term) => $term->term_dates)
            ->reject(fn ($term_date) => $term_date->is_concert)
            ->where('start_datetime', '>', Carbon::now())
            ->sortBy('start_datetime')
            ->first();

        $next_concert = $upcoming_terms
            ->flatMap(fn ($term) => $term->term_dates)
            ->filter(fn ($term_date) => $term_date->concert_ensembles->contains($ensemble))
            ->where('start_datetime', '>', Carbon::now())
            ->sortBy('start_datetime')
            ->first();

        return view('ensembles.show', [
            'ensemble' => $ensemble,
            'upcomingTerms' => $upcoming_terms,
            'nextRehearsal' => $next_rehearsal,
            'nextConcert' => $next_concert,
            'page_name' => $ensemble->name,
        ]);
    }
}
