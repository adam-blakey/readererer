<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSetlistRequest;
use App\Http\Requests\UpdateSetlistRequest;
use App\Models\Setlist;

class SetlistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setlists = Setlist::whereNull('deleted_at')->autosort()->paginate(10);

        return view('auto-entities.index', [
            'entities' => $setlists,
            'page_name' => 'Setlists',
            'page_subname' => 'Setlist overview'
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
    public function store(StoreSetlistRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Setlist $setlist)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Setlist $setlist)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSetlistRequest $request, Setlist $setlist)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setlist $setlist)
    {
        //
    }
}