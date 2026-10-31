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
            'page_name' => __('Setlists'),
            'page_subname' => __('Setlist overview'),
        ]);
    }

    public function create() {}

    public function store(StoreSetlistRequest $request) {}

    public function show(Setlist $setlist) {}

    public function edit(Setlist $setlist) {}

    public function update(UpdateSetlistRequest $request, Setlist $setlist) {}

    public function destroy(Setlist $setlist)
    {
        $setlist->delete();

        return redirect()->back()->with('status', __('Record deleted.'));
    }

    public function restore(int $id)
    {
        $entity = Setlist::withTrashed()->findOrFail($id);
        $this->authorize('restore', $entity);
        $entity->restore();

        return redirect()->back()->with('status', __('Restored.'));
    }
}
