<?php

namespace App\Http\Controllers;

use App\Models\SetupGroup;
use App\Http\Requests\UpdateSetupGroupRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                'route' => 'setupgroups.create',
                'name' => 'setup group'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $dummy = new SetupGroup();
        $fields = get_create_fields($dummy);
//        $fields = [
//            "name" => [
//                "label" => "Name",
//                "type" => "text",
//                "required" => true,
//                "icon" => $dummy->getIconForAttribute("name") ?? 'pencil',
//                "width" => 12
//            ],
//            "week" => [
//                "label" => "Week",
//                "type" => "text",
//                "required" => true,
//                "icon" => $dummy->getIconForAttribute("week") ?? 'pencil',
//                "width" => 6
//            ],
//            "color" => [
//                "label" => "Color",
//                "type" => "text",
//                "required" => true,
//                "icon" => $dummy->getIconForAttribute("color") ?? 'pencil',
//                "width" => 6
//            ]
//        ];

        return view('auto-entities.form', [
            'page_name' => 'Setup groups',
            'page_subname' => 'Create new setup group',
            'update' => false,
            'fields' => $fields,
            'form_route' => route('setupgroups.store')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // TODO: better validation; maybe automatic somehow?
        $attributes = $request->validate([
            'name' => 'required',
            'week' => 'required',
            'color' => 'required',
        ]);

        $setup_group = SetupGroup::create($attributes);

        return to_route('setupgroups.show', $setup_group);
    }

    public function edit(SetupGroup $setupGroup): View
    {
        $fields = get_create_fields($setupGroup);
//        $fields = [
//            "name" => [
//                "label" => "Name",
//                "type" => "text",
//                "required" => true,
//                "icon" => $setupGroup->getIconForAttribute("name") ?? 'pencil',
//                "width" => 12,
//                "value" => $setupGroup->name
//            ],
//            "week" => [
//                "label" => "Week",
//                "type" => "text",
//                "required" => true,
//                "icon" => $setupGroup->getIconForAttribute("week") ?? 'pencil',
//                "width" => 6,
//                "value" => $setupGroup->week
//            ],
//            "color" => [
//                "label" => "Color",
//                "type" => "text",
//                "required" => true,
//                "icon" => $setupGroup->getIconForAttribute("color") ?? 'pencil',
//                "width" => 6,
//                "value" => $setupGroup->color
//            ]
//        ];

        return view('auto-entities.form', [
            'page_name' => 'Setup groups',
            'page_subname' => 'Update setup group',
            'update' => true,
            'fields' => $fields,
            'form_route' => route('setupgroups.update', $setupGroup)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function update(Request $request, SetupGroup $setupGroup): RedirectResponse
    {
        // TODO: better validation; maybe automatic somehow?
        $attributes = $request->validate([
            'name' => 'required',
            'week' => 'required',
            'color' => 'required',
        ]);

        $setupGroup->update($attributes);

        return to_route('setupgroups.show', $setupGroup);
    }

    /**
     * Display the specified resource.
     */
    public function show(SetupGroup $setupGroup)
    {
        return view('auto-entities.show', [
            'entity' => $setupGroup,
            'page_name' => 'Setup groups',
            'page_subname' => 'Setup group ' . $setupGroup->name,
            'edit_route' => 'setupgroups.edit',
            'destroy_route' => 'setupgroups.destroy',
            'restore_route' => 'setupgroups.restore'
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

        // Not sure why this is necessary...
        $entity->deleted_at = null;
        $entity->save();

        return redirect()->back()->with('status', 'Restored.');
    }
}
