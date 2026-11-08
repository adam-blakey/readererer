<?php

namespace App\Http\Controllers;

use App\Enums\EmailLinkType;
use App\Enums\UserRole;
use App\Http\Requests\EraseUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\SetupGroup;
use App\Models\User;
use App\Models\UserEnsemble;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class);
    }

    public function index()
    {
        $query = User::query();
        $query->when(request('with_trashed'), fn ($query) => $query->withTrashed());
        $ensemble_options = Ensemble::orderBy('name')->pluck('name', 'id')->all() + ['none' => __('No ensemble')];
        $chosen = array_intersect(array_keys($ensemble_options), array_map('strval', Arr::wrap(request('ensembles'))));
        if (count($chosen) > 0 && count($chosen) < count($ensemble_options)) {
            $ensemble_ids = array_diff($chosen, ['none']);
            $query = $query->where(fn ($query) => $query
                ->when($ensemble_ids, fn ($query) => $query->whereHas('ensembles', fn ($query) => $query->whereKey($ensemble_ids)))
                ->when(in_array('none', $chosen), fn ($query) => $query->orWhereDoesntHave('ensembles')));
        }
        $users = $query->with(['ensembles', 'setup_group'])->autosort()->paginate(10)->appends(request()->only('with_trashed', 'ensembles'));

        return view('auto-entities.index', [
            'entities' => $users,
            'page_name' => __('Users'),
            'page_subname' => __('Users overview'),
            'ensemble_filter' => $ensemble_options,
            'create_entity' => [
                'route' => 'users.create',
                'name' => __('user'),
            ],
        ]);
    }

    public function create(): View
    {
        $fields = Arr::except(get_create_fields(new User), User::hiddenFields());
        $fields['first_name']['width'] = 6;
        $fields['last_name']['width'] = 6;
        unset($fields['password']);
        unset($fields['image']);
        $fields['role']['options'] = array_filter($fields['role']['options'], fn ($role) => $role <= auth()->user()->role->value, ARRAY_FILTER_USE_KEY);

        if (! config('app.readererer_setup_groups')) {
            unset($fields['setup_group']);
        }

        if (single_ensemble()) {
            $fields['instrument_family'] = [
                'label' => __('Instrument family'),
                'type' => 'class',
                'required' => true,
                'icon' => 'guitar-pick',
                'value' => null,
                'options' => InstrumentFamily::orderBy('name')->get(),
                'default_option' => null,
                'select_multiple' => false,
                'width' => 12,
            ];
        }

        return view('auto-entities.form', [
            'page_name' => __('Users'),
            'page_subname' => __('Create new user'),
            'update' => false,
            'fields' => $fields,
            'form_route' => route('users.store'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $attributes = $request->validated();
        $attributes['password'] = Str::password(16);
        $setup_group_id = Arr::pull($attributes, 'setup_group');
        $instrument_family_id = Arr::pull($attributes, 'instrument_family');

        // Someone else may take the generated username between the check and the insert.
        $user = retry(3, fn () => User::create($attributes + [
            'username' => User::generateUniqueUsername($attributes['first_name'], $attributes['last_name']),
        ]), when: fn (Throwable $exception) => $exception instanceof UniqueConstraintViolationException);
        $user->setup_group()->associate($setup_group_id ? SetupGroup::find($setup_group_id) : null);
        $user->must_change_password = true;
        $user->save();

        if ($ensemble = single_ensemble()) {
            $user->ensembles()->attach($ensemble, ['instrument_family_id' => $instrument_family_id]);
        }

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
            'roles' => UserRole::assignableBy(auth()->user()),
            'setupGroups' => SetupGroup::orderBy('name')->get(),
            'ensembles' => Ensemble::orderBy('name')->get(),
            'allInstrumentFamilies' => InstrumentFamily::orderBy('name')->get(),
            'instrumentFamilies' => $instrumentFamilies,
            'page_name' => __('Edit :name', ['name' => $user->name]),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $attributes = $request->validated();
        $changes_setup_group = array_key_exists('setup_group', $attributes);
        $setup_group_id = Arr::pull($attributes, 'setup_group');

        $user->applyImageInput($request->file('image'), $request->boolean('remove_image'));
        $user->update(Arr::except($attributes, ['image', 'remove_image']));
        if (config('app.readererer_setup_groups') && $changes_setup_group) {
            $user->setup_group()->associate($setup_group_id ? SetupGroup::find($setup_group_id) : null);
        }

        $user->save();

        return to_route('users.show', $user);
    }

    public function show(User $user)
    {
        abort_if($user->trashed() && ! request()->boolean('with_trashed'), 404);

        $user->load(['ensembles', 'setup_group', 'van_driver_groups']);

        $instrumentFamilies = InstrumentFamily::whereIn(
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

        return redirect()->back()->with('status', __('Record deleted.'));
    }

    public function restore(int $userId)
    {
        $user = User::withTrashed()->findOrFail($userId);
        $this->authorize('restore', $user);
        $user->restore();

        return redirect()->back()->with('status', __('User restored.'));
    }

    public function erase(EraseUserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($user) {
            $email = $user->email;

            $user->forceFill([
                'first_name' => __('Former member'),
                'last_name' => '#'.$user->id,
                'email' => null,
                'username' => 'erased-'.$user->id,
                'password' => Str::random(64),
                'remember_token' => null,
                'email_verified_at' => null,
                'must_change_password' => false,
                'image' => null,
                'setup_group_id' => null,
                'phone_number' => null,
                'address_line1' => null,
                'address_line2' => null,
                'address_city' => null,
                'address_post_code' => null,
                'emergency_contact_name' => null,
                'emergency_contact_number' => null,
                'emergency_contact_relationship' => null,
                'emergency_contact_address_line1' => null,
                'emergency_contact_address_line2' => null,
                'emergency_contact_address_city' => null,
                'emergency_contact_address_post_code' => null,
                'date_of_birth' => null,
                'has_photo_permission' => false,
                'is_gift_aiding_subs' => false,
            ])->save();

            $user->ensembles()->detach();
            DB::table('ensemble_admins')->where('admin_id', $user->id)->delete();
            DB::table('setup_group_van_driver')->where('user_id', $user->id)->delete();
            DB::table('term_dates')->where('van_driver_id', $user->id)->update(['van_driver_id' => null]);

            DB::table('sessions')->where('user_id', $user->id)->delete();
            if ($email !== null) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
            }

            DB::table('attendances')->where('user_id', $user->id)->orWhere('edit_user_id', $user->id)->update(['edit_ip' => '']);
            DB::table('register_entries')->where('user_id', $user->id)->update(['notes' => null]);
            DB::table('email_recipients')->where('user_id', $user->id)->update(['name' => null, 'email' => null, 'error_message' => null]);

            if (! $user->trashed()) {
                $user->delete();
            }
        });

        return to_route('users.index')->with('status', __('Personal data erased.'));
    }

    public function sendPasswordResetLink(User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);
        abort_if(blank($user->email), 422);

        $status = EmailLinkType::ResetPassword->send($user->email);

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __('A password reset email has been sent to :email.', ['email' => $user->email]))
            : back()->withErrors(['reset_link' => __($status)]);
    }

    public function setPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
            'must_change_password' => $request->boolean('must_change_password'),
        ])->save();

        return back()->with('status', __('The password for :name has been set.', ['name' => $user->name]));
    }

    public function attachEnsemble(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manage', $user);

        $validated = $request->validate([
            'ensemble_id' => 'required|exists:ensembles,id',
            'instrument_family_id' => 'required|exists:instrument_families,id',
            'seat_row' => 'nullable|string|max:10',
            'seat_column' => 'nullable|string|max:10',
        ]);

        if ($user->ensembles()->where('ensembles.id', $validated['ensemble_id'])->exists()) {
            return redirect()->back()->with('status', __('User is already a member of that ensemble.'));
        }

        $ensemble = Ensemble::findOrFail($validated['ensemble_id']);
        $seat = $ensemble->seating_plan_enabled
            ? ['seat_row' => $validated['seat_row'] ?? null, 'seat_column' => $validated['seat_column'] ?? null]
            : ['seat_row' => null, 'seat_column' => null];

        $user->ensembles()->attach($validated['ensemble_id'], array_merge([
            'instrument_family_id' => $validated['instrument_family_id'],
        ], $seat));

        return redirect()->back()->with('status', __('User added to ensemble.'));
    }

    public function detachEnsemble(User $user, Ensemble $ensemble): RedirectResponse
    {
        $this->authorize('manage', $user);

        $pivot = UserEnsemble::where('user_id', $user->id)
            ->where('ensemble_id', $ensemble->id)
            ->firstOrFail();

        $pivot->delete();

        return redirect()->back()->with('status', __('User removed from ensemble.'));
    }
}
