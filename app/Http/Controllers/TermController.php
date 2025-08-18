<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Http\Requests\StoreTermRequest;
use App\Http\Requests\UpdateTermRequest;
use App\Models\Ensemble;

class TermController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $terms = Term::whereNull('deleted_at')->with(['term_dates'])->withCount('term_dates')->autosort()->paginate(10);

        return view('auto-entities.index', [
            'entities' => $terms,
            'page_name' => 'Terms',
            'page_subname' => 'Terms overview'
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
    public function store(StoreTermRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Term $term)
    {
        $term->load('term_dates');

        return view('terms.show', [
            'term' => $term,
            'page_name' => $term->name,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Term $term)
    {
        $term->load('term_dates');
        $ensembles = Ensemble::whereNull('deleted_at')->orderBy('name')->get();
        return view('terms.edit', [
            'term' => $term,
            'page_name' => 'Edit term',
            'ensembles' => $ensembles,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTermRequest $request, Term $term)
    {
        $attributes = $request->safe()->only(['name', 'slug']);

        $request_term_dates = collect($request->input('term_dates', []));

        /// Delete term dates that are not in the request
        $keepIds = $request_term_dates->pluck('id')->filter()->values()->all();

        if (count($keepIds) > 0) {
            $term->term_dates()->whereNotIn('id', $keepIds)->delete();
        } else {
            // If no IDs provided, remove all existing term dates
            $term->term_dates()->delete();
        }

        // Create or update term dates
        $request_term_dates->each(function ($term_date) use ($term) {
            $payload = [
                'start_datetime' => $term_date['start_datetime'] ?? null,
                'end_datetime'   => $term_date['end_datetime'] ?? null,
                'ensemble_id'    => $term_date['ensemble_id'] ?? null,
            ];

            if (!empty($term_date['id'])) {
                // Update existing by ID, scoped to this term
                $term->term_dates()->whereKey($term_date['id'])->update($payload);
            } else {
                // Create new
                $term->term_dates()->create($payload);
            }
        });

        $term->fill($attributes);
        $term->save();

        return redirect()->route('terms.show', $term);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Term $term)
    {
        //
    }
}
