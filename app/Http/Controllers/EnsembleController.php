<?php

namespace App\Http\Controllers;

use App\Models\Ensemble;
use App\Http\Requests\StoreEnsembleRequest;
use App\Http\Requests\UpdateEnsembleRequest;
use App\Models\Term;
use Illuminate\Support\Carbon;

class EnsembleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Ensemble::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ensembles = Ensemble::whereNull('deleted_at')->with(['admins'])->autosort()->paginate(10);

        return view('auto-entities.index', [
            'entities' => $ensembles,
            'page_name' => 'Ensembles',
            'page_subname' => 'Ensemble overview'
        ]);
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
    public function store(StoreEnsembleRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Ensemble $ensemble)
    {
        $upcoming_terms = Term::where('latest_date', '>', Carbon::now())
            ->with('term_dates')
            ->orderBy('latest_date')
            ->get();

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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ensemble $ensemble)
    {
        return view('ensembles.edit', [
            'ensemble' => $ensemble,
            'page_name' => 'Edit ' . $ensemble->name
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEnsembleRequest $request, Ensemble $ensemble)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ensemble $ensemble)
    {
        //
    }
}
