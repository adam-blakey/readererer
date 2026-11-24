<?php

namespace App\Http\Controllers;

use App\Features\ConcertDetails;
use App\Http\Requests\UpdateTermRequest;
use App\Models\DressCode;
use App\Models\Ensemble;
use App\Models\Location;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\User;
use App\Services\VanDriverRota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Pennant\Feature;

class TermController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Term::class);
    }

    public function index()
    {
        $query = Term::query();
        $query->when(request('with_trashed'), fn ($query) => $query->withTrashed());
        if (! request('with_past')) {
            $query = $query->current();
        }
        $terms = $query->with(['term_dates.concert_ensembles'])->withCount('term_dates')->autosort()->paginate(per_page())->withQueryString();

        return view('auto-entities.index', [
            'entities' => $terms,
            'page_name' => __('Terms'),
            'page_subname' => __('Terms overview'),
            'show_past_toggle' => true,
            'create_entity' => [
                'route' => 'terms.create',
                'name' => __('term'),
            ],
        ]);
    }

    public function create(): View
    {
        $term = new Term;
        $ensembles = Ensemble::shown()->orderBy('name')->get();
        $setup_groups = SetupGroup::orderBy('name')->get();
        $van_drivers = User::orderBy('first_name')->orderBy('last_name')->get();

        return view('terms.form', [
            'term' => $term,
            'page_name' => __('New term'),
            'ensembles' => $ensembles,
            'setup_groups' => $setup_groups,
            'van_drivers' => $van_drivers,
            'locations' => $this->locationOptions($term),
            'dress_codes' => $this->dressCodeOptions($term),
            'form_route' => route('terms.store'),
            'form_method' => 'POST',
        ]);
    }

    public function store(Request $request, VanDriverRota $rota): RedirectResponse
    {
        $single = single_ensemble();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:terms',
            'location_id' => 'nullable|integer|exists:locations,id',
            'hidden' => 'sometimes|boolean',

            'term_dates' => 'array',
            'term_dates.*.id' => 'nullable|integer',
            'term_dates.*.start_datetime' => 'required|date',
            'term_dates.*.end_datetime' => 'required|date',
            'term_dates.*.ensemble_ids' => 'array',
            'term_dates.*.ensemble_ids.*' => ['integer', 'exists:ensembles,id', ...($single ? [Rule::in([$single->id])] : [])],
            'term_dates.*.setup_group_id' => 'nullable|integer|exists:setup_groups,id',
            'term_dates.*.van_driver_id' => 'nullable|integer|exists:users,id',
            'term_dates.*.time_tbc' => 'sometimes|boolean',
            'term_dates.*.location_id' => 'nullable|integer|exists:locations,id',
            'term_dates.*.description' => 'nullable|string|max:5000',
            'term_dates.*.concert_order' => 'nullable|string|max:5000',
            'term_dates.*.dress_code_id' => 'nullable|integer|exists:dress_codes,id',
        ]);

        $term = $rota->notifyingChanges(fn () => DB::transaction(function () use ($validated) {
            $term = Term::create(Arr::only($validated, ['name', 'slug', 'location_id', 'hidden']));
            $this->syncTermDates($term, $validated['term_dates'] ?? []);

            return $term;
        }));

        return redirect()->route('terms.show', $term);
    }

    public function show(Term $term)
    {
        $term->load('location', 'term_dates.concert_ensembles', 'term_dates.location', 'term_dates.van_driver', 'term_dates.setup_group.van_drivers');

        return view('terms.show', [
            'term' => $term,
            'page_name' => $term->name,
        ]);
    }

    public function edit(Term $term)
    {
        $term->load('term_dates.concert_ensembles');
        // A hidden ensemble a date already plays in stays selectable, so saving the form keeps it.
        $ensembles = Ensemble::shown()
            ->orWhereIn('id', $term->term_dates->flatMap->concert_ensembles->pluck('id'))
            ->orderBy('name')
            ->get();
        $setup_groups = SetupGroup::orderBy('name')->get();
        $van_drivers = User::orderBy('first_name')->orderBy('last_name')->get();

        return view('terms.form', [
            'term' => $term,
            'page_name' => __('Edit term'),
            'ensembles' => $ensembles,
            'setup_groups' => $setup_groups,
            'van_drivers' => $van_drivers,
            'locations' => $this->locationOptions($term),
            'dress_codes' => $this->dressCodeOptions($term),
            'form_route' => route('terms.update', $term),
            'form_method' => 'PATCH',
        ]);
    }

    public function update(UpdateTermRequest $request, Term $term, VanDriverRota $rota)
    {
        $rota->notifyingChanges(fn () => DB::transaction(function () use ($request, $term) {
            $this->syncTermDates($term, $request->validated('term_dates', []));
            $term->update($request->safe()->only(['name', 'slug', 'location_id', 'hidden']));
        }));

        return redirect()->route('terms.show', $term);
    }

    public function destroy(Term $term, VanDriverRota $rota)
    {
        $rota->notifyingChanges(fn () => DB::transaction(fn () => $term->delete()));

        return redirect()->back()->with('status', __('Record deleted.'));
    }

    public function restore(Term $term, VanDriverRota $rota)
    {
        $rota->notifyingChanges(fn () => DB::transaction(fn () => $term->restore()));

        return redirect()->back()->with('status', __('Restored.'));
    }

    /** Every live location, plus any archived one a date still uses so saving the form keeps it. */
    private function locationOptions(Term $term)
    {
        return Location::withTrashed()
            ->where(fn ($query) => $query->whereNull('deleted_at')->orWhereIn('id', $term->term_dates->pluck('location_id')->push($term->location_id)->filter()))
            ->orderBy('name')
            ->get();
    }

    private function dressCodeOptions(Term $term)
    {
        return DressCode::withTrashed()
            ->where(fn ($query) => $query->whereNull('deleted_at')->orWhereIn('id', $term->term_dates->pluck('dress_code_id')->filter()))
            ->orderBy('name')
            ->get();
    }

    private function syncTermDates(Term $term, array $rows): void
    {
        $rows = collect($rows);

        $term->term_dates()->whereNotIn('id', $rows->pluck('id')->filter())->delete();

        foreach ($rows as $row) {
            $payload = array_filter([
                'start_datetime' => $row['start_datetime'],
                'end_datetime' => $row['end_datetime'],
                'setup_group_id' => $row['setup_group_id'] ?? null,
                'van_driver_id' => $row['van_driver_id'] ?? null,
                'time_tbc' => ! empty($row['time_tbc']),
                'location_id' => $row['location_id'] ?? null,
                'description' => $row['description'] ?? null,
                'concert_order' => $row['concert_order'] ?? null,
                'dress_code_id' => $row['dress_code_id'] ?? null,
            ], fn (string $key) => match ($key) {
                'setup_group_id' => config('app.readererer_setup_groups'),
                'van_driver_id' => config('app.readererer_van_drivers'),
                'description', 'concert_order', 'dress_code_id' => Feature::active(ConcertDetails::class),
                default => true,
            }, ARRAY_FILTER_USE_KEY);

            if (! empty($row['id'])) {
                // Fetched first: a query-builder update() would skip TermDateObserver's change alerts.
                $termDate = $term->term_dates()->whereKey($row['id'])->first();
                $termDate?->update($payload);
            } else {
                $termDate = $term->term_dates()->create($payload);
            }

            $termDate?->concert_ensembles()->sync($row['ensemble_ids'] ?? []);
        }
    }
}
