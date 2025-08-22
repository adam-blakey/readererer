<?php

namespace App\Http\Controllers;

use App\Models\SetupGroup;
use App\Http\Requests\StoreSetupGroupRequest;
use App\Http\Requests\UpdateSetupGroupRequest;

class SetupGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setupGroups = SetupGroup::whereNull('deleted_at')->autosort()->paginate(10);

        return view('auto-entities.index', [
            'entities' => $setupGroups,
            'page_name' => 'Setup groups',
            'page_subname' => 'Setup groups overview',
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
    public function store(StoreSetupGroupRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SetupGroup $setupGroup)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SetupGroup $setupGroup)
    {
        return view('setup-groups.edit', [
            'setup_group' => $setupGroup,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSetupGroupRequest $request, SetupGroup $setupGroup)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SetupGroup $setupGroup)
    {
        //
    }
}
