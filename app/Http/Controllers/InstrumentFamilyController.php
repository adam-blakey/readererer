<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInstrumentFamilyRequest;
use App\Http\Requests\UpdateInstrumentFamilyRequest;
use App\Models\InstrumentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InstrumentFamilyController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(InstrumentFamily::class, 'instrumentFamily');
    }

    public function index()
    {
        $query = InstrumentFamily::query();
        if (request('with_trashed')) {
            $query = $query->withTrashed();
        } else {
            $query = $query->whereNull('deleted_at');
        }
        $instrumentFamilies = $query->autosort()->paginate(10)->appends(request()->only('with_trashed'));

        return view('auto-entities.index', [
            'entities' => $instrumentFamilies,
            'page_name' => __('Instrument families'),
            'page_subname' => __('Instrument families overview'),
            'create_entity' => [
                'route' => 'instrumentfamilys.create',
                'name' => __('instrument family'),
            ],
        ]);
    }

    public function create(): View
    {
        $dummy = new InstrumentFamily;
        $fields = get_create_fields($dummy);

        return view('auto-entities.form', [
            'page_name' => __('Instrument families'),
            'page_subname' => __('Create new instrument family'),
            'update' => false,
            'fields' => $fields,
            'form_route' => route('instrumentfamilys.store'),
        ]);
    }

    public function store(StoreInstrumentFamilyRequest $request): RedirectResponse
    {
        $instrumentFamily = InstrumentFamily::create($request->validated());

        return to_route('instrumentfamilys.show', $instrumentFamily);
    }

    public function show(InstrumentFamily $instrumentFamily)
    {
        return view('auto-entities.show', [
            'entity' => $instrumentFamily,
            'page_name' => __('Instrument families'),
            'page_subname' => __('Instrument family :name', ['name' => $instrumentFamily->name]),
            'edit_route' => 'instrumentfamilys.edit',
            'destroy_route' => 'instrumentfamilys.destroy',
            'restore_route' => 'instrumentfamilys.restore',
        ]);
    }

    public function edit(InstrumentFamily $instrumentFamily): View
    {
        $fields = get_create_fields($instrumentFamily);

        return view('auto-entities.form', [
            'page_name' => __('Instrument families'),
            'page_subname' => __('Update instrument family'),
            'update' => true,
            'fields' => $fields,
            'form_route' => route('instrumentfamilys.update', $instrumentFamily),
        ]);
    }

    public function update(UpdateInstrumentFamilyRequest $request, InstrumentFamily $instrumentFamily): RedirectResponse
    {
        $instrumentFamily->update($request->validated());

        return to_route('instrumentfamilys.show', $instrumentFamily);
    }

    public function destroy(InstrumentFamily $instrumentFamily)
    {
        $instrumentFamily->delete();

        return redirect()->back()->with('status', __('Record deleted.'));
    }

    public function restore(int $id)
    {
        $entity = InstrumentFamily::withTrashed()->findOrFail($id);
        $this->authorize('restore', $entity);
        $entity->restore();

        return redirect()->back()->with('status', __('Restored.'));
    }
}
