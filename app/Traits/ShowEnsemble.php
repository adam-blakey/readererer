<?php

namespace App\Traits;

use App\Features\EnsembleViewInstrument;
use App\Models\Ensemble;
use App\Models\Term;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Feature;

trait ShowEnsemble
{
    public function show(Ensemble $ensemble)
    {
        $upcoming_terms = Term::current()->visibleTo(auth()->user())
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

        $membership = Feature::active(EnsembleViewInstrument::class)
            ? auth()->user()->ensembles->firstWhere('id', $ensemble->id)?->pivot
            : null;

        return view('ensembles.show', [
            'ensemble' => $ensemble,
            'membership' => $membership,
            'upcomingTerms' => $upcoming_terms,
            'nextRehearsal' => $next_rehearsal,
            'nextConcert' => $next_concert,
            'page_name' => $ensemble->name,
        ]);
    }
}
