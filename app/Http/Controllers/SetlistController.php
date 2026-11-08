<?php

namespace App\Http\Controllers;

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
        $query->when(request('with_trashed'), fn ($query) => $query->withTrashed());
        $setlists = $query->autosort()->paginate(10)->appends(request()->only('with_trashed'));

        return view('auto-entities.index', [
            'entities' => $setlists,
            'page_name' => __('Setlists'),
            'page_subname' => __('Setlist overview'),
        ]);
    }

    public function destroy(Setlist $setlist)
    {
        $setlist->delete();

        return redirect()->back()->with('status', __('Record deleted.'));
    }

    public function restore(Setlist $setlist)
    {
        $setlist->restore();

        return redirect()->back()->with('status', __('Restored.'));
    }
}
