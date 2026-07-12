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

    /**
     * Display a listing of the resource.
     */
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
            'page_name' => 'Instrument families',
            'page_subname' => 'Instrument families overview',
            'create_entity' => [
                'route' => 'instrumentfamilys.create',
                'name' => 'instrument family',
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $dummy = new InstrumentFamily;
        $fields = get_create_fields($dummy);

        return view('auto-entities.form', [
            'page_name' => 'Instrument families',
            'page_subname' => 'Create new instrument family',
            'update' => false,
            'fields' => $fields,
            'form_route' => route('instrumentfamilys.store'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInstrumentFamilyRequest $request): RedirectResponse
    {
        $instrumentFamily = InstrumentFamily::create($request->validated());

        return to_route('instrumentfamilys.show', $instrumentFamily);
    }

    /**
     * Display the specified resource.
     */
    public function show(InstrumentFamily $instrumentFamily)
    {
        return view('auto-entities.show', [
            'entity' => $instrumentFamily,
            'page_name' => 'Instrument families',
            'page_subname' => 'Instrument family '.$instrumentFamily->name,
            'edit_route' => 'instrumentfamilys.edit',
            'destroy_route' => 'instrumentfamilys.destroy',
            'restore_route' => 'instrumentfamilys.restore',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InstrumentFamily $instrumentFamily): View
    {
        $fields = get_create_fields($instrumentFamily);

        return view('auto-entities.form', [
            'page_name' => 'Instrument families',
            'page_subname' => 'Update instrument family',
            'update' => true,
            'fields' => $fields,
            'form_route' => route('instrumentfamilys.update', $instrumentFamily),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInstrumentFamilyRequest $request, InstrumentFamily $instrumentFamily): RedirectResponse
    {
        $instrumentFamily->update($request->validated());

        return to_route('instrumentfamilys.show', $instrumentFamily);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InstrumentFamily $instrumentFamily)
    {
        $instrumentFamily->delete();

        return redirect()->back()->with('status', 'Record deleted.');
    }

    public function purgeTrashed()
    {
        InstrumentFamily::onlyTrashed()->get()->each(function ($model) {
            $model->forceDelete();
        });

        return redirect()->back()->with('status', 'All soft-deleted records permanently removed.');
    }

    public function restore(int $id)
    {
        $entity = InstrumentFamily::withTrashed()->findOrFail($id);
        $this->authorize('restore', $entity);
        $entity->restore();

        return redirect()->back()->with('status', 'Restored.');
    }
}
