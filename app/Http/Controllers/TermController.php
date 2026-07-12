<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTermRequest;
use App\Models\Ensemble;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class TermController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Term::query();
        if (request('with_trashed')) {
            $query = $query->withTrashed();
        } else {
            $query = $query->whereNull('deleted_at');
        }
        $terms = $query->with(['term_dates'])->withCount('term_dates')->autosort()->paginate(10)->appends(request()->only('with_trashed'));

        return view('auto-entities.index', [
            'entities' => $terms,
            'page_name' => 'Terms',
            'page_subname' => 'Terms overview',
            'create_entity' => [
                'route' => 'terms.create',
                'name' => 'term',
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $term = new Term;
        $ensembles = Ensemble::whereNull('deleted_at')->orderBy('name')->get();
        $setup_groups = SetupGroup::orderBy('name')->get();
        $van_drivers = User::orderBy('first_name')->orderBy('last_name')->get();

        return view('terms.form', [
            'term' => $term,
            'page_name' => 'New term',
            'ensembles' => $ensembles,
            'setup_groups' => $setup_groups,
            'van_drivers' => $van_drivers,
            'form_route' => route('terms.store'),
            'form_method' => 'POST',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:terms',

            'term_dates' => 'array',
            'term_dates.*.id' => 'nullable|integer',
            'term_dates.*.start_datetime' => 'required|date',
            'term_dates.*.end_datetime' => 'required|date',
            'term_dates.*.ensemble_id' => 'nullable|integer|exists:ensembles,id',
            'term_dates.*.setup_group_id' => 'nullable|integer|exists:setup_groups,id',
            'term_dates.*.van_driver_id' => 'nullable|integer|exists:users,id',
        ]);

        $attributes = Arr::only($validated, ['name', 'slug']);

        $request_term_dates = collect($request->input('term_dates', []));

        $term = Term::create($attributes);

        // Create or update term dates
        $request_term_dates->each(function ($term_date) use ($term) {
            $payload = [
                'start_datetime' => $term_date['start_datetime'] ?? null,
                'end_datetime' => $term_date['end_datetime'] ?? null,
                'concert_ensemble_id' => $term_date['ensemble_id'] ?? null,
                'setup_group_id' => $term_date['setup_group_id'] ?? null,
                'van_driver_id' => $term_date['van_driver_id'] ?? null,
            ];

            if (! empty($term_date['id'])) {
                // Update existing by ID, scoped to this term
                $term->term_dates()->whereKey($term_date['id'])->update($payload);
            } else {
                // Create new
                $term->term_dates()->create($payload);
            }
        });

        $term->save();

        return redirect()->route('terms.show', $term);
    }

    /**
     * Display the specified resource.
     */
    public function show(Term $term)
    {
        $term->load('term_dates')->with('van_driver');
        $ensembles = Ensemble::orderBy('name')->get();

        // Attendance totals per term date: rehearsals count every playing member,
        // concerts only the concert ensemble's members.
        $members = User::with(['attendances', 'ensembles'])->get();
        $attendance_totals = $term->term_dates->mapWithKeys(function ($term_date) use ($members) {
            $playing = $members->filter(function ($member) use ($term_date) {
                $memberships = $term_date->concert_ensemble_id
                    ? $member->ensembles->where('id', $term_date->concert_ensemble_id)
                    : $member->ensembles;

                return $memberships->contains(fn ($ensemble) => $ensemble->pivot->instrument_family_id != null);
            });

            return [$term_date->id => member_status_totals($playing, $term_date)];
        });

        return view('terms.show', [
            'term' => $term,
            'ensembles' => $ensembles,
            'attendance_totals' => $attendance_totals,
            'page_name' => $term->name,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Term $term)
    {
        $term->load('term_dates');
        $ensembles = Ensemble::whereNull('deleted_at')->orderBy('name')->get();
        $setup_groups = SetupGroup::orderBy('name')->get();
        $van_drivers = User::orderBy('first_name')->orderBy('last_name')->get();

        return view('terms.form', [
            'term' => $term,
            'page_name' => 'Edit term',
            'ensembles' => $ensembles,
            'setup_groups' => $setup_groups,
            'van_drivers' => $van_drivers,
            'form_route' => route('terms.update', $term),
            'form_method' => 'PATCH',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTermRequest $request, Term $term)
    {
        $attributes = $request->safe()->only(['name', 'slug']);

        $request_term_dates = collect($request->input('term_dates', []));

        // / Delete term dates that are not in the request
        $keepIds = $request_term_dates->pluck('id')->filter()->values()->all();

        if (count($keepIds) > 0) {
            $term->term_dates()->whereNotIn('id', $keepIds)->delete();
        } else {
            // If no IDs provided, remove all existing term dates
            $term->term_dates()->delete();
        }

        // Create or update term dates
        $request_term_dates->each(function ($term_date) use ($term) {
            $payload = [
                'start_datetime' => $term_date['start_datetime'] ?? null,
                'end_datetime' => $term_date['end_datetime'] ?? null,
                'concert_ensemble_id' => $term_date['ensemble_id'] ?? null,
                'setup_group_id' => $term_date['setup_group_id'] ?? null,
                'van_driver_id' => $term_date['van_driver_id'] ?? null,
            ];

            if (! empty($term_date['id'])) {
                // Update existing by ID, scoped to this term
                $term->term_dates()->whereKey($term_date['id'])->update($payload);
            } else {
                // Create new
                $term->term_dates()->create($payload);
            }
        });

        $term->fill($attributes);
        $term->save();

        return redirect()->route('terms.show', $term);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Term $term)
    {
        $term->delete();

        return redirect()->back()->with('status', 'Record deleted.');
    }

    public function purgeTrashed()
    {
        Term::onlyTrashed()->get()->each(function ($model) {
            $model->forceDelete();
        });

        return redirect()->back()->with('status', 'All soft-deleted records permanently removed.');
    }

    public function restore(int $id)
    {
        $entity = Term::withTrashed()->findOrFail($id);
        $entity->restore();

        return redirect()->back()->with('status', 'Restored.');
    }
}
