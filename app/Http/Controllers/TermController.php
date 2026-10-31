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
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TermController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Term::class);
    }

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
            'page_name' => __('Terms'),
            'page_subname' => __('Terms overview'),
            'create_entity' => [
                'route' => 'terms.create',
                'name' => __('term'),
            ],
        ]);
    }

    public function create(): View
    {
        $term = new Term;
        $ensembles = Ensemble::whereNull('deleted_at')->orderBy('name')->get();
        $setup_groups = SetupGroup::orderBy('name')->get();
        $van_drivers = User::orderBy('first_name')->orderBy('last_name')->get();

        return view('terms.form', [
            'term' => $term,
            'page_name' => __('New term'),
            'ensembles' => $ensembles,
            'setup_groups' => $setup_groups,
            'van_drivers' => $van_drivers,
            'form_route' => route('terms.store'),
            'form_method' => 'POST',
        ]);
    }

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

        $term = DB::transaction(function () use ($validated) {
            $term = Term::create(Arr::only($validated, ['name', 'slug']));
            $this->syncTermDates($term, $validated['term_dates'] ?? []);

            return $term;
        });

        return redirect()->route('terms.show', $term);
    }

    public function show(Term $term)
    {
        $term->load('term_dates')->with('van_driver');
        $ensembles = Ensemble::orderBy('name')->get();

        return view('terms.show', [
            'term' => $term,
            'ensembles' => $ensembles,
            'page_name' => $term->name,
        ]);
    }

    public function edit(Term $term)
    {
        $term->load('term_dates');
        $ensembles = Ensemble::whereNull('deleted_at')->orderBy('name')->get();
        $setup_groups = SetupGroup::orderBy('name')->get();
        $van_drivers = User::orderBy('first_name')->orderBy('last_name')->get();

        return view('terms.form', [
            'term' => $term,
            'page_name' => __('Edit term'),
            'ensembles' => $ensembles,
            'setup_groups' => $setup_groups,
            'van_drivers' => $van_drivers,
            'form_route' => route('terms.update', $term),
            'form_method' => 'PATCH',
        ]);
    }

    public function update(UpdateTermRequest $request, Term $term)
    {
        DB::transaction(function () use ($request, $term) {
            $this->syncTermDates($term, $request->validated('term_dates', []));
            $term->update($request->safe()->only(['name', 'slug']));
        });

        return redirect()->route('terms.show', $term);
    }

    public function destroy(Term $term)
    {
        $term->delete();

        return redirect()->back()->with('status', __('Record deleted.'));
    }

    public function restore(int $id)
    {
        $entity = Term::withTrashed()->findOrFail($id);
        $this->authorize('restore', $entity);
        $entity->restore();

        return redirect()->back()->with('status', __('Restored.'));
    }

    private function syncTermDates(Term $term, array $rows): void
    {
        $rows = collect($rows);

        $term->term_dates()->whereNotIn('id', $rows->pluck('id')->filter())->delete();

        foreach ($rows as $row) {
            $payload = [
                'start_datetime' => $row['start_datetime'],
                'end_datetime' => $row['end_datetime'],
                'concert_ensemble_id' => $row['ensemble_id'] ?? null,
                'setup_group_id' => $row['setup_group_id'] ?? null,
                'van_driver_id' => $row['van_driver_id'] ?? null,
            ];

            if (! empty($row['id'])) {
                // Fetched first so the update fires TermDateObserver's change alerts;
                // a query-builder update() skips model events.
                $term->term_dates()->whereKey($row['id'])->first()?->update($payload);
            } else {
                $term->term_dates()->create($payload);
            }
        }
    }
}
