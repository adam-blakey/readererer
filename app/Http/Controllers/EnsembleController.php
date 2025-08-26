<?php

namespace App\Http\Controllers;

use App\Models\Ensemble;
use App\Http\Requests\StoreEnsembleRequest;
use App\Http\Requests\UpdateEnsembleRequest;
use App\Models\Term;
use App\ShowEnsemble;
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
        $query = Ensemble::query();
        if (request('with_trashed')) {
            $query = $query->withTrashed();
        } else {
            $query = $query->whereNull('deleted_at');
        }
        $ensembles = $query->with(['admins'])->autosort()->paginate(10)->appends(request()->only('with_trashed'));

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

    use ShowEnsemble;

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
        $ensemble->delete();
        return redirect()->back()->with('status', 'Record deleted.');
    }

    public function purgeTrashed()
    {
        Ensemble::onlyTrashed()->get()->each(function ($model) {
            $model->forceDelete();
        });
        return redirect()->back()->with('status', 'All soft-deleted records permanently removed.');
    }

    public function restore(int $id)
    {
        $entity = Ensemble::withTrashed()->findOrFail($id);
        $entity->restore();

        // Not sure why this is necessary...
        $entity->deleted_at = null;
        $entity->save();

        return redirect()->back()->with('status', 'Restored.');
    }
}
