<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSetlistRequest;
use App\Http\Requests\UpdateSetlistRequest;
use App\Models\Setlist;
class SetlistController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Setlist::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Setlist::query();
        if (request('with_trashed')) {
            $query = $query->withTrashed();
        } else {
            $query = $query->whereNull('deleted_at');
        }
        $setlists = $query->autosort()->paginate(10)->appends(request()->only('with_trashed'));

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
        $setlist->delete();
        return redirect()->back()->with('status', 'Record deleted.');
    }

    public function purgeTrashed()
    {
        Setlist::onlyTrashed()->get()->each(function ($model) {
            $model->forceDelete();
        });
        return redirect()->back()->with('status', 'All soft-deleted records permanently removed.');
    }

    public function restore(int $id)
    {
        $entity = Setlist::withTrashed()->findOrFail($id);
        $entity->restore();

        // Not sure why this is necessary...
        $entity->deleted_at = null;
        $entity->save();

        return redirect()->back()->with('status', 'Restored.');
    }
}
