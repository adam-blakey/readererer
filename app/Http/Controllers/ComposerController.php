<?php

namespace App\Http\Controllers;

use App\Models\Composer;

class ComposerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Composer::class);
    }

    public function index()
    {
        $query = Composer::query();
        $query->when(request('with_trashed'), fn ($query) => $query->withTrashed());
        $composers = $query->autosort()->paginate(10)->appends(request()->only('with_trashed'));

        return view('auto-entities.index', [
            'entities' => $composers,
            'page_name' => __('Composers'),
            'page_subname' => __('Composers overview'),
        ]);
    }

    public function destroy(Composer $composer)
    {
        $composer->delete();

        return redirect()->back()->with('status', __('Record deleted.'));
    }

    public function restore(Composer $composer)
    {
        $composer->restore();

        return redirect()->back()->with('status', __('Restored.'));
    }
}
