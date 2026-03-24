<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'page_subname' => 'Users overview'
        ]);
    }

    public function create(): View
    {
        //$fields = get_create_fields(new User);
        $fields = [
            [
                "name" => "first_name",
                "label" => "First name",
                "type" => "text",
                "required" => true,
                "icon" => "user",
                "width" => 6
            ],
            [
                "name" => "last_name",
                "label" => "Last name",
                "type" => "text",
                "required" => true,
                "icon" => "user",
                "width" => 6
            ],
            [
                "name" => "email",
                "label" => "Email",
                "type" => "email",
                "required" => false,
                "icon" => "mail",
                "width" => 12
            ],
            [
                "name" => "role",
                "label" => "Role",
                "type" => "enum",
                "options" => UserRole::cases(),
                "default_option" => UserRole::Member,
                "required" => true,
                "icon" => "user-star",
                "width" => 12
            ]
        ];

        return view('auto-entities.create', [
            'page_name' => 'Users',
            'page_subname' => 'Create new user',
            'fields' => $fields
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // TODO: better validation; maybe automatic somehow?
        $attributes = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'role' => [Rule::enum(UserRole::class)]
        ]);

        // TODO: Need to be careful on username collisions.
        $attributes['username'] = Str::slug($attributes['first_name'] . ' ' . $attributes['last_name'], '.');
        $attributes['password'] = Str::password(16);

        $user = User::create($attributes);

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
