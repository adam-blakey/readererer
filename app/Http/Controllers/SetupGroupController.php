<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSetupGroupRequest;
use App\Http\Requests\UpdateSetupGroupRequest;
use App\Models\SetupGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class SetupGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = SetupGroup::query();
        if (request('with_trashed')) {
            $query = $query->withTrashed();
        } else {
            $query = $query->whereNull('deleted_at');
        }
        $setupGroups = $query->autosort()->paginate(10)->appends(request()->only('with_trashed'));

        return view('auto-entities.index', [
            'entities' => $setupGroups,
            'page_name' => 'Setup groups',
            'page_subname' => 'Setup groups overview',
            'create_entity' => [
                'route' => 'setup-groups.create',
                'name' => 'setup group',
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $dummy = new SetupGroup;
        $fields = get_create_fields($dummy);

        return view('auto-entities.form', [
            'page_name' => 'Setup groups',
            'page_subname' => 'Create new setup group',
            'update' => false,
            'fields' => $fields,
            'form_route' => route('setup-groups.store'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSetupGroupRequest $request): RedirectResponse
    {
        $attributes = $request->validated();

        $van_drivers = Arr::pull($attributes, 'van_drivers') ?? [];
        $setup_group = SetupGroup::create($attributes);

        $setup_group->van_drivers()->detach();
        foreach ($van_drivers as $van_driver) {
            $setup_group->van_drivers()->attach($van_driver);
        }

        return to_route('setup-groups.show', $setup_group);
    }

    public function edit(SetupGroup $setupGroup): View
    {
        $fields = get_create_fields($setupGroup);
        $fields['van_drivers']['options'] = $fields['van_drivers']['options']->sortBy('first_name');

        return view('auto-entities.form', [
            'page_name' => 'Setup groups',
            'page_subname' => 'Update setup group',
            'update' => true,
            'fields' => $fields,
            'form_route' => route('setup-groups.update', $setupGroup),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function update(UpdateSetupGroupRequest $request, SetupGroup $setupGroup): RedirectResponse
    {
        $attributes = $request->validated();

        $van_drivers = Arr::pull($attributes, 'van_drivers') ?? [];
        $setupGroup->update($attributes);

        $setupGroup->van_drivers()->detach();
        foreach ($van_drivers as $van_driver) {
            $setupGroup->van_drivers()->attach($van_driver);
        }

        return to_route('setup-groups.show', $setupGroup);
    }

    /**
     * Display the specified resource.
     */
    public function show(SetupGroup $setupGroup)
    {
        return view('auto-entities.show', [
            'entity' => $setupGroup,
            'page_name' => 'Setup groups',
            'page_subname' => 'Setup group '.$setupGroup->name,
            'edit_route' => 'setup-groups.edit',
            'destroy_route' => 'setup-groups.destroy',
            'restore_route' => 'setup-groups.restore',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SetupGroup $setupGroup)
    {
        $setupGroup->delete();

        return redirect()->back()->with('status', 'Record deleted.');
    }

    public function purgeTrashed()
    {
        SetupGroup::onlyTrashed()->get()->each(function ($model) {
            $model->forceDelete();
        });

        return redirect()->back()->with('status', 'All soft-deleted records permanently removed.');
    }

    public function restore(int $id)
    {
        $entity = SetupGroup::withTrashed()->findOrFail($id);
        $entity->restore();

        return redirect()->back()->with('status', 'Restored.');
    }
}
