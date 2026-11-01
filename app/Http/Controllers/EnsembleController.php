<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnsembleRequest;
use App\Http\Requests\UpdateEnsembleRequest;
use App\Models\Ensemble;
use App\Models\User;
use App\Models\UserEnsemble;
use App\Traits\ShowEnsemble;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EnsembleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Ensemble::class);
    }

    public function index()
    {
        $query = Ensemble::query();
        if (request('with_trashed')) {
            $query = $query->withTrashed();
        } else {
            $query = $query->whereNull('deleted_at');
        }
        $ensembles = $query->with(['admins'])->withCount('users')->autosort()->paginate(10)->appends(request()->only('with_trashed'));

        return view('auto-entities.index', [
            'entities' => $ensembles,
            'page_name' => __('Ensembles'),
            'page_subname' => __('Ensemble overview'),
            'create_entity' => [
                'route' => 'ensembles.create',
                'name' => __('ensemble'),
            ],
        ]);
    }

    public function create(): View
    {
        $fields = get_create_fields(new Ensemble);

        return view('auto-entities.form', [
            'page_name' => __('Ensembles'),
            'page_subname' => __('Create new ensemble'),
            'update' => false,
            'fields' => $fields,
            'form_route' => route('ensembles.store'),
        ]);
    }

    public function store(StoreEnsembleRequest $request): RedirectResponse
    {
        $attributes = $request->validated();

        $ensemble = new Ensemble($attributes);
        $ensemble->slug = Str::slug($attributes['name'], '_');
        $ensemble->save();

        return to_route('ensembles.show', $ensemble);
    }

    use ShowEnsemble;

    public function edit(Ensemble $ensemble)
    {
        return view('ensembles.edit', [
            'ensemble' => $ensemble,
            'page_name' => __('Edit :name', ['name' => $ensemble->name]),
        ]);
    }

    public function update(UpdateEnsembleRequest $request, Ensemble $ensemble): RedirectResponse
    {
        $ensemble->name = $request->validated('name');
        $ensemble->slug = $request->validated('slug');
        $ensemble->seating_plan_enabled = $request->boolean('seating_plan_enabled');
        $ensemble->applyImageInput($request->file('image'), $request->boolean('remove_image'));
        $ensemble->save();

        return to_route('ensembles.show', $ensemble);
    }

    public function destroy(Ensemble $ensemble)
    {
        $ensemble->delete();

        return redirect()->back()->with('status', __('Record deleted.'));
    }

    public function restore(int $id)
    {
        $entity = Ensemble::withTrashed()->findOrFail($id);
        $this->authorize('restore', $entity);
        $entity->restore();

        return redirect()->back()->with('status', __('Restored.'));
    }

    public function members(Ensemble $ensemble): View
    {
        $this->authorize('view', $ensemble);

        return view('ensembles.members', [
            'ensemble' => $ensemble,
            'page_name' => $ensemble->name,
            'page_subname' => __('Members'),
        ]);
    }

    public function addMember(Ensemble $ensemble)
    {
        $this->authorize('update', $ensemble);

        $validated = request()->validate([
            'user_id' => 'required|exists:users,id',
            'instrument_family_id' => 'required|exists:instrument_families,id',
            'seat_row' => 'nullable|string|max:10',
            'seat_column' => 'nullable|string|max:10',
        ]);

        $seat = $ensemble->seating_plan_enabled
            ? ['seat_row' => $validated['seat_row'] ?? null, 'seat_column' => $validated['seat_column'] ?? null]
            : ['seat_row' => null, 'seat_column' => null];

        $ensemble->users()->attach($validated['user_id'], array_merge([
            'instrument_family_id' => $validated['instrument_family_id'],
        ], $seat));

        return redirect()->back()->with('status', __('User added to ensemble.'));
    }

    public function removeMember(Ensemble $ensemble, User $user)
    {
        $this->authorize('update', $ensemble);

        $pivot = UserEnsemble::where('user_id', $user->id)
            ->where('ensemble_id', $ensemble->id)
            ->firstOrFail();

        $pivot->delete();

        return redirect()->back()->with('status', __('User removed from ensemble.'));
    }
}
