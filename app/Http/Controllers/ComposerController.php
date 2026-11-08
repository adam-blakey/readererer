<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComposerRequest;
use App\Http\Requests\UpdateComposerRequest;
use App\Models\Composer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ComposerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Composer::class);
    }

    public function index()
    {
        $query = Composer::query();
        if (request('with_trashed')) {
            $query = $query->withTrashed();
        } else {
            $query = $query->whereNull('deleted_at');
        }
        $composers = $query->autosort()->paginate(10)->appends(request()->only('with_trashed'));

        return view('auto-entities.index', [
            'entities' => $composers,
            'page_name' => __('Composers'),
            'page_subname' => __('Composers overview'),
        ]);
    }

    public function create() {}

    public function store(StoreComposerRequest $request) {}

    public function show(Composer $composer): View
    {
        return view('auto-entities.show', [
            'entity' => $composer,
            'page_name' => __('Composers'),
            'page_subname' => __('Composer :name', ['name' => $composer->name]),
            'edit_route' => 'composers.edit',
            'destroy_route' => 'composers.destroy',
            'restore_route' => 'composers.restore',
        ]);
    }

    public function edit(Composer $composer): View
    {
        return view('auto-entities.form', [
            'page_name' => __('Composers'),
            'page_subname' => __('Update composer'),
            'update' => true,
            'fields' => get_create_fields($composer),
            'form_route' => route('composers.update', $composer),
        ]);
    }

    public function update(UpdateComposerRequest $request, Composer $composer): RedirectResponse
    {
        $composer->update($request->validated());

        return to_route('composers.show', $composer);
    }

    public function destroy(Composer $composer)
    {
        $composer->delete();

        return redirect()->back()->with('status', __('Record deleted.'));
    }

    public function restore(int $id)
    {
        $entity = Composer::withTrashed()->findOrFail($id);
        $this->authorize('restore', $entity);
        $entity->restore();

        return redirect()->back()->with('status', __('Restored.'));
    }
}
