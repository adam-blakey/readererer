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
        $users = User::latest()->paginate(10);

        return view('users.index', [
            'users' => $users,
            'page_name' => 'Users'
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
}