<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = User::query();
        if (request('with_trashed')) {
            $query = $query->withTrashed();
        } else {
            $query = $query->whereNull('deleted_at');
        }
        $users = $query->autosort()->paginate(10)->appends(request()->only('with_trashed'));

        return view('auto-entities.index', [
            'entities' => $users,
            'page_name' => 'Users',
            'page_subname' => 'Users overview'
        ]);
    }

    public function show(User $user)
    {
        $user->load(['ensembles']);

        $instrumentFamilies = \App\Models\InstrumentFamily::whereIn(
            'id', $user->ensembles->pluck('pivot.instrument_family_id')->unique()
        )->get()->keyBy('id');

        return view('users.show', [
            'user' => $user,
            'instrumentFamilies' => $instrumentFamilies,
            'page_name' => $user->name
        ]);
    }
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->back()->with('status', 'Record deleted.');
    }

    public function restore(int $userId)
    {
        $user = User::withTrashed()->findOrFail($userId);
        $user->restore();

        // Not sure why this is necessary...
        $user->deleted_at = null;
        $user->save();

        return redirect()->back()->with('status', 'User restored.');
    }
}
