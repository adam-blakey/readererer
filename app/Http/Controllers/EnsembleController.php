<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEnsembleRequest;
use App\Models\Ensemble;
use App\Models\User;
use App\Models\UserEnsemble;
use App\Traits\ShowEnsemble;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EnsembleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Ensemble::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Ensemble::query();
        if (request('with_trashed')) {
            $query = $query->withTrashed();
        } else {
            $query = $query->whereNull('deleted_at');
        }
        $ensembles = $query->with(['admins'])->autosort()->paginate(10)->appends(request()->only('with_trashed'));

        return view('auto-entities.index', [
            'entities' => $ensembles,
            'page_name' => 'Ensembles',
            'page_subname' => 'Ensemble overview',
            'create_entity' => [
                'route' => 'ensembles.create',
                'name' => 'ensemble'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        //$fields = get_create_fields(new User);
        $fields = [
            [
                "name" => "name",
                "label" => "Name",
                "type" => "text",
                "required" => true,
                "icon" => "pencil",
                "width" => 12
            ]
        ];

        return view('auto-entities.form', [
            'page_name' => 'Ensembles',
            'page_subname' => 'Create new ensemble',
            'update' => false,
            'fields' => $fields,
            'form_route' => route('ensembles.store')
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
        ]);

        $attributes['slug'] = Str::slug($attributes['name'], '_');

        $ensemble = Ensemble::create($attributes);

        return to_route('ensembles.show', $ensemble);
    }

    use ShowEnsemble;

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ensemble $ensemble)
    {
        return view('ensembles.edit', [
            'ensemble' => $ensemble,
            'page_name' => 'Edit ' . $ensemble->name
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEnsembleRequest $request, Ensemble $ensemble)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ensemble $ensemble)
    {
        $ensemble->delete();
        return redirect()->back()->with('status', 'Record deleted.');
    }

    public function purgeTrashed()
    {
        Ensemble::onlyTrashed()->get()->each(function ($model) {
            $model->forceDelete();
        });
        return redirect()->back()->with('status', 'All soft-deleted records permanently removed.');
    }

    public function restore(int $id)
    {
        $entity = Ensemble::withTrashed()->findOrFail($id);
        $entity->restore();

        // Not sure why this is necessary...
        $entity->deleted_at = null;
        $entity->save();

        return redirect()->back()->with('status', 'Restored.');
    }

    public function add_user(Ensemble $ensemble)
    {
        $validated = request()->validate([
            'user_id' => 'required|exists:users,id',
            'instrument_family_id' => 'required|exists:instrument_families,id',
            'seat_row' => 'nullable|string|max:10',
            'seat_column' => 'nullable|string|max:10',
        ]);

        $ensemble->users()->attach($validated['user_id'], [
            'instrument_family_id' => $validated['instrument_family_id'],
            'seat_row' => $validated['seat_row'],
            'seat_column' => $validated['seat_column'],
        ]);

        return redirect()->back()->with('status', 'User added to ensemble.');
    }

    public function remove_user(Ensemble $ensemble, User $user)
    {
        $pivot = UserEnsemble::where('user_id', $user->id)
            ->where('ensemble_id', $ensemble->id)
            ->firstOrFail();

        $pivot->delete();

        return redirect()->back()->with('status', 'User removed from ensemble.');
    }
}
