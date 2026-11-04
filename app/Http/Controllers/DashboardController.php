<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\EmailLog;
use App\Models\TermDate;
use App\Traits\ShowEnsemble;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

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
        $ensembles = $user->ensembles()->get();
        $attendanceUpdates = $user->can('viewAny', Attendance::class)
            ? Attendance::latestUpdates()->limit(5)->get()
            : null;
        $notifications = $user->can('view.notifications')
            ? EmailLog::with(['recipients', 'termDate'])->latest()->limit(5)->get()
            : null;

        if ($ensembles->isEmpty()) {
            return view('dashboard.index', compact('page_name', 'ensembles', 'attendanceUpdates', 'notifications'));
        }

        $setupGroup = $user->setup_group;

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

        $drivesTheVan = $nextVanDrive !== null || (bool) $setupGroup?->van_drivers->contains($user);

        return view('dashboard.index', compact(
            'page_name',
            'ensembles',
            'setupGroup',
            'nextRehearsal',
            'nextConcerts',
            'nextSetup',
            'nextVanDrive',
            'drivesTheVan',
            'attendanceUpdates',
            'notifications',
        ));
    }
}
