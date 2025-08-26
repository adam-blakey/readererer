<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use App\Http\Requests\StorePieceRequest;
use App\Http\Requests\UpdatePieceRequest;

class PieceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Piece::query();
        if (request('with_trashed')) {
            $query = $query->withTrashed();
        } else {
            $query = $query->whereNull('deleted_at');
        }
        $pieces = $query->with(['composer'])->autosort()->paginate(10)->appends(request()->only('with_trashed'));

        return view('auto-entities.index', [
            'entities' => $pieces,
            'page_name' => 'Pieces',
            'page_subname' => 'Pieces overview'
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
    public function store(StorePieceRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Piece $piece)
    {
        return view('pieces.show', [
            'piece' => $piece,
            'page_name' => $piece->title
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Piece $piece)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePieceRequest $request, Piece $piece)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Piece $piece)
    {
        $piece->delete();
        return redirect()->back()->with('status', 'Record deleted.');
    }

    public function purgeTrashed()
    {
        Piece::onlyTrashed()->get()->each(function ($model) {
            $model->forceDelete();
        });
        return redirect()->back()->with('status', 'All soft-deleted records permanently removed.');
    }

    public function restore(int $id)
    {
        $entity = Piece::withTrashed()->findOrFail($id)->restore();

        // Not sure why this is necessary...
        $entity->deleted_at = null;
        $entity->save();

        return redirect()->back()->with('status', 'Restored.');
    }
}
