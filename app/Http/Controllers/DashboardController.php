<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
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

        $ensembles = $user->ensembles()->get();
        $setupGroup = $user->setup_group;

        $nextRehearsal = null;
        if ($ensembles->isNotEmpty()) {
            $nextRehearsal = TermDate::whereNull('concert_ensemble_id')
                ->where('start_datetime', '>', Carbon::now())
                ->orderBy('start_datetime')
                ->first();
        }

        $ensembleIds = $ensembles->pluck('id');
        $nextConcerts = TermDate::whereNotNull('concert_ensemble_id')
            ->whereIn('concert_ensemble_id', $ensembleIds)
            ->where('start_datetime', '>', Carbon::now())
            ->orderBy('start_datetime')
            ->limit(3)
            ->get();

        $nextVanDrive = null;
        if ($setupGroup) {
            $upcomingWithGroup = TermDate::where('setup_group_id', $setupGroup->id)
                ->where('start_datetime', '>', Carbon::now())
                ->orderBy('start_datetime')
                ->get();

            $nextVanDrive = $upcomingWithGroup->first(function ($td) use ($user) {
                return optional($td->inferred_van_driver)->id === $user->id;
            });
        }

        return view('dashboard.index', [
            'page_name' => __(':app dashboard', ['app' => config('app.name')]),
            'ensembles' => $ensembles,
            'setupGroup' => $setupGroup,
            'nextRehearsal' => $nextRehearsal,
            'nextConcerts' => $nextConcerts,
            'nextVanDrive' => $nextVanDrive,
        ]);
    }
}
