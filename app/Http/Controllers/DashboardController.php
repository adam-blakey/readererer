<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Features\DashboardAdminCards;
use App\Features\DashboardPartAndSeat;
use App\Models\Attendance;
use App\Models\EmailLog;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;
use App\Traits\ShowEnsemble;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Pennant\Feature;

class DashboardController extends Controller
{
    use ShowEnsemble;

    public function index()
    {
        $user = Auth::user();

        if ($user->role == UserRole::Ensemble) {
            $ensemble = $user->ensembles()->first();
            abort_if($ensemble === null, 403, __('This login is not linked to an ensemble.'));

            return $this->show($ensemble);
        }

        $page_name = __(':app dashboard', ['app' => config('app.name')]);
        $ensembles = $user->ensembles()->shown()->get();
        $attendanceUpdates = $user->can('viewAny', Attendance::class)
            ? Attendance::latestUpdates()->limit(5)->get()
            : null;
        $instrumentFamilies = Feature::active(DashboardPartAndSeat::class)
            ? InstrumentFamily::whereIn('id', $ensembles->pluck('pivot.instrument_family_id')->filter())->get()->keyBy('id')
            : null;
        $managementCards = Feature::active(DashboardAdminCards::class) ? $this->managementCards($user) : [];
        $notifications = $user->can('view.notifications')
            ? EmailLog::with(['recipients', 'termDate'])->newestOrSorted()->limit(5)->get()
            : null;

        if ($ensembles->isEmpty()) {
            return view('dashboard.index', compact('page_name', 'ensembles', 'instrumentFamilies', 'attendanceUpdates', 'notifications', 'managementCards'));
        }

        $setupGroup = config('app.readererer_setup_groups') ? $user->setup_group : null;

        $nextRehearsal = TermDate::whereDoesntHave('concert_ensembles')
            ->where('start_datetime', '>', Carbon::now())
            ->orderBy('start_datetime')
            ->first();

        $nextConcerts = TermDate::with('concert_ensembles')
            ->whereHas('concert_ensembles', fn ($query) => $query->whereKey($ensembles->pluck('id')))
            ->where('start_datetime', '>', Carbon::now())
            ->orderBy('start_datetime')
            ->limit(3)
            ->get();

        $nextSetup = $setupGroup
            ? TermDate::where('setup_group_id', $setupGroup->id)
                ->where('start_datetime', '>', Carbon::now())
                ->orderBy('start_datetime')
                ->first()
            : null;

        $nextVanDrive = TermDate::where('start_datetime', '>', Carbon::now())
            ->where(fn ($query) => $query
                ->where('van_driver_id', $user->id)
                ->when($setupGroup, fn ($query) => $query->orWhere('setup_group_id', $setupGroup->id)))
            ->orderBy('start_datetime')
            ->get()
            ->first(fn (TermDate $termDate) => $termDate->inferred_van_driver?->id === $user->id);

        $drivesTheVan = config('app.readererer_van_drivers')
            && ($nextVanDrive !== null || (bool) $setupGroup?->van_drivers->contains($user));

        return view('dashboard.index', compact(
            'page_name',
            'ensembles',
            'instrumentFamilies',
            'setupGroup',
            'nextRehearsal',
            'nextConcerts',
            'nextSetup',
            'nextVanDrive',
            'drivesTheVan',
            'attendanceUpdates',
            'notifications',
            'managementCards',
        ));
    }

    private function managementCards(User $user): array
    {
        return collect([
            ['title' => __('Ensembles'), 'icon' => 'users-group', 'route' => 'ensembles.index', 'model' => Ensemble::class],
            config('app.readererer_setup_groups') ? ['title' => __('Setup groups'), 'icon' => 'truck', 'route' => 'setupgroups.index', 'model' => SetupGroup::class] : null,
            ['title' => __('Users'), 'icon' => 'user', 'route' => 'users.index', 'model' => User::class],
            ['title' => __('Terms'), 'icon' => 'calendar', 'route' => 'terms.index', 'model' => Term::class],
        ])
            ->filter(fn (?array $card) => $card && $user->can('viewAny', $card['model']))
            ->map(fn (array $card) => $card + ['count' => $card['model']::count()])
            ->when($user->can('view.settings'), fn ($cards) => $cards->push(['title' => __('Settings'), 'icon' => 'settings', 'route' => 'settings.edit', 'count' => null]))
            ->all();
    }
}
