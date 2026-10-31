<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePieceRequest;
use App\Http\Requests\UpdatePieceRequest;
use App\Models\Piece;

class PieceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Piece::class);
    }

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
            'page_name' => __('Pieces'),
            'page_subname' => __('Pieces overview'),
        ]);
    }

    public function create() {}

    public function store(StorePieceRequest $request) {}

    public function show(Piece $piece)
    {
        return view('pieces.show', [
            'piece' => $piece,
            'page_name' => $piece->title,
        ]);
    }

    public function edit(Piece $piece) {}

    public function update(UpdatePieceRequest $request, Piece $piece) {}

    public function destroy(Piece $piece)
    {
        $piece->delete();

        return redirect()->back()->with('status', __('Record deleted.'));
    }

    public function restore(int $id)
    {
        $entity = Piece::withTrashed()->findOrFail($id);
        $this->authorize('restore', $entity);
        $entity->restore();

        return redirect()->back()->with('status', __('Restored.'));
    }
}
