<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\SetupGroup;
use App\Models\User;
use App\Models\UserEnsemble;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = User::query();
        if (request('with_trashed')) {
            $query = $query->withTrashed();
        } else {
            $query = $query->whereNull('deleted_at');
        }
        $users = $query->autosort()->paginate(10)->appends(request()->only('with_trashed'));

        return view('auto-entities.index', [
            'entities' => $users,
            'page_name' => 'Users',
            'page_subname' => 'Users overview',
            'create_entity' => [
                'route' => 'users.create',
                'name' => 'user',
            ],
        ]);
    }

    public function create(): View
    {
        $fields = get_create_fields(new User);
        $fields['first_name']['width'] = 6;
        $fields['last_name']['width'] = 6;
        unset($fields['password']);
        unset($fields['image']);
        $fields['role']['type'] = 'enum';
        $fields['role']['options'] = UserRole::cases();
        $fields['role']['default_option'] = UserRole::Member;

        return view('auto-entities.form', [
            'page_name' => 'Users',
            'page_subname' => 'Create new user',
            'update' => false,
            'fields' => $fields,
            'form_route' => route('users.store'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $attributes = $request->validated();

        $attributes['username'] = User::generateUniqueUsername($attributes['first_name'], $attributes['last_name']);
        $attributes['password'] = Str::password(16);

        $setup_group_id = Arr::pull($attributes, 'setup_group');
        $user = User::create($attributes);
        $setup_group = SetupGroup::find($setup_group_id);
        $user->setup_group()->associate($setup_group);

        return to_route('users.show', $user);
    }

    public function edit(User $user): View
    {
        $user->load(['ensembles', 'setup_group']);

        $instrumentFamilies = InstrumentFamily::whereIn(
            'id', $user->ensembles->pluck('pivot.instrument_family_id')->unique()
        )->get()->keyBy('id');

        return view('users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
            'setupGroups' => SetupGroup::orderBy('name')->get(),
            'ensembles' => Ensemble::orderBy('name')->get(),
            'allInstrumentFamilies' => InstrumentFamily::orderBy('name')->get(),
            'instrumentFamilies' => $instrumentFamilies,
            'page_name' => 'Edit '.$user->name,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $attributes = $request->validated();

        $setup_group_id = Arr::pull($attributes, 'setup_group');
        $setup_group = SetupGroup::find($setup_group_id);
        $user->update($attributes);
        $user->setup_group()->associate($setup_group);

        $user->save();

        return to_route('users.show', $user);
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
            'page_name' => $user->name,
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->back()->with('status', 'Record deleted.');
    }

    public function restore(int $userId)
    {
        $user = User::withTrashed()->findOrFail($userId);
        $user->restore();

        return redirect()->back()->with('status', 'User restored.');
    }

    /**
     * Add the user to an ensemble from the user edit page.
     */
    public function attachEnsemble(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'ensemble_id' => 'required|exists:ensembles,id',
            'instrument_family_id' => 'required|exists:instrument_families,id',
            'seat_row' => 'nullable|string|max:10',
            'seat_column' => 'nullable|string|max:10',
        ]);

        if ($user->ensembles()->where('ensembles.id', $validated['ensemble_id'])->exists()) {
            return redirect()->back()->with('status', 'User is already a member of that ensemble.');
        }

        $user->ensembles()->attach($validated['ensemble_id'], [
            'instrument_family_id' => $validated['instrument_family_id'],
            'seat_row' => $validated['seat_row'] ?? null,
            'seat_column' => $validated['seat_column'] ?? null,
        ]);

        return redirect()->back()->with('status', 'User added to ensemble.');
    }

    /**
     * Remove the user from an ensemble from the user edit page.
     */
    public function detachEnsemble(User $user, Ensemble $ensemble): RedirectResponse
    {
        $pivot = UserEnsemble::where('user_id', $user->id)
            ->where('ensemble_id', $ensemble->id)
            ->firstOrFail();

        $pivot->delete();

        return redirect()->back()->with('status', 'User removed from ensemble.');
    }
}
