<?php

namespace App\Http\Controllers;

use App\Models\Composer;
use App\Http\Requests\StoreComposerRequest;
use App\Http\Requests\UpdateComposerRequest;

class ComposerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Composer::query();
        if (request('with_trashed')) {
            $query = $query->withTrashed();
        } else {
            $query = $query->whereNull('deleted_at');
        }
        $composers = $query->autosort()->paginate(10)->appends(request()->only('with_trashed'));

        return view('auto-entities.index', [
            'entities' => $composers,
            'page_name' => 'Composers',
            'page_subname' => 'Composers overview'
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
    public function store(StoreComposerRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Composer $composer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Composer $composer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateComposerRequest $request, Composer $composer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Composer $composer)
    {
        $composer->delete();
        return redirect()->back()->with('status', 'Record deleted.');
    }

    public function purgeTrashed()
    {
        Composer::onlyTrashed()->get()->each(function ($model) {
            $model->forceDelete();
        });
        return redirect()->back()->with('status', 'All soft-deleted records permanently removed.');
    }

    public function restore(int $id)
    {
        $entity = Composer::withTrashed()->findOrFail($id);
        $entity->restore();

        // Not sure why this is necessary...
        $entity->deleted_at = null;
        $entity->save();

        return redirect()->back()->with('status', 'Restored.');
    }
}
