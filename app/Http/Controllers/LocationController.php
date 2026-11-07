<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationRequest;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Location::class);
    }

    public function index()
    {
        $query = Location::query();
        if (request('with_trashed')) {
            $query = $query->withTrashed();
        } else {
            $query = $query->whereNull('deleted_at');
        }
        $locations = $query->autosort()->paginate(10)->appends(request()->only('with_trashed'));

        return view('auto-entities.index', [
            'entities' => $locations,
            'page_name' => __('Locations'),
            'page_subname' => __('Locations overview'),
            'create_entity' => [
                'route' => 'locations.create',
                'name' => __('location'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('auto-entities.form', [
            'page_name' => __('Locations'),
            'page_subname' => __('Create new location'),
            'update' => false,
            'fields' => get_create_fields(new Location),
            'form_route' => route('locations.store'),
        ]);
    }

    public function store(StoreLocationRequest $request): RedirectResponse|JsonResponse
    {
        $location = new Location($request->validated());
        $location->geocode();
        $location->save();

        if ($request->wantsJson()) {
            return response()->json($location->only('id', 'name'), 201);
        }

        return to_route('locations.show', $location);
    }

    public function show(Location $location)
    {
        return view('auto-entities.show', [
            'entity' => $location,
            'page_name' => __('Locations'),
            'page_subname' => __('Location :name', ['name' => $location->name]),
            'edit_route' => 'locations.edit',
            'destroy_route' => 'locations.destroy',
            'restore_route' => 'locations.restore',
            'below' => 'locations.map',
            'location' => $location,
        ]);
    }

    public function edit(Location $location): View
    {
        return view('auto-entities.form', [
            'page_name' => __('Locations'),
            'page_subname' => __('Update location'),
            'update' => true,
            'fields' => get_create_fields($location),
            'form_route' => route('locations.update', $location),
            'below' => 'locations.map',
            'location' => $location,
        ]);
    }

    public function update(StoreLocationRequest $request, Location $location): RedirectResponse
    {
        $location->fill($request->validated());
        if ($location->isDirty('address') && ! $location->isDirty(['latitude', 'longitude'])) {
            $location->latitude = $location->longitude = null;
        }
        $location->geocode();
        $location->save();

        return to_route('locations.show', $location);
    }

    public function destroy(Location $location)
    {
        $location->delete();

        return redirect()->back()->with('status', __('Record deleted.'));
    }

    public function restore(int $id)
    {
        $entity = Location::withTrashed()->findOrFail($id);
        $this->authorize('restore', $entity);
        $entity->restore();

        return redirect()->back()->with('status', __('Restored.'));
    }
}
