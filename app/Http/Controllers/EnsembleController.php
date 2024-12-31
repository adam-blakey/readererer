<?php

namespace App\Http\Controllers;

use App\Models\Ensemble;
use App\Http\Requests\StoreEnsembleRequest;
use App\Http\Requests\UpdateEnsembleRequest;

class EnsembleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ensembles = Ensemble::latest()->with(['admins'])->paginate(10);

        return view('ensembles.index', [
            'ensembles' => $ensembles,
            'page_name' => 'Ensembles'
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
        return view('ensembles.show', [
            'ensemble' => $ensemble,
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