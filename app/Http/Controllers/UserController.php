<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\SetupGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
                'name' => 'user'
            ]
        ]);
    }

    public function create(): View
    {
        $fields = get_create_fields(new User);
        $fields["first_name"]["width"] = 6;
        $fields["last_name"]["width"] = 6;
        unset($fields["password"]);
        unset($fields["image"]);
        $fields["role"]["type"] = "enum";
        $fields["role"]["options"] = UserRole::cases();
        $fields["role"]["default_option"] = UserRole::Member;

        return view('auto-entities.form', [
            'page_name' => 'Users',
            'page_subname' => 'Create new user',
            'update' => false,
            'fields' => $fields,
            'form_route' => route('users.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // TODO: better validation; maybe automatic somehow?
        $attributes = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'email',
            'role' => [Rule::enum(UserRole::class)],
            'setup_group' => 'required|exists:setup_groups,id',
        ]);

        // TODO: Need to be careful on username collisions.
        $attributes['username'] = Str::slug($attributes['first_name'] . ' ' . $attributes['last_name'], '.');
        $attributes['password'] = Str::password(16);

        $setup_group_id = Arr::pull($attributes, 'setup_group');
        $user = User::create($attributes);
        $setup_group = SetupGroup::find($setup_group_id);
        $user->setup_group()->associate($setup_group);

        return to_route('users.show', $user);
    }

    public function edit(User $user): View
    {
        $fields = get_create_fields($user);
        $fields["first_name"]["width"] = 6;
        $fields["last_name"]["width"] = 6;
        unset($fields["password"]);
        unset($fields["image"]);
        $fields["role"]["type"] = "enum";
        $fields["role"]["options"] = UserRole::cases();

        return view('auto-entities.form', [
            'page_name' => 'Users',
            'page_subname' => 'Update user',
            'update' => true,
            'fields' => $fields,
            'form_route' => route('users.update', $user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        // TODO: better validation; maybe automatic somehow?
        $attributes = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'email',
            'role' => [Rule::enum(UserRole::class)],
            'setup_group' => 'required|exists:setup_groups,id'
        ]);

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
            'page_name' => $user->name
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

        // Not sure why this is necessary...
        $user->deleted_at = null;
        $user->save();

        return redirect()->back()->with('status', 'User restored.');
    }
}
