<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\ShowEnsemble;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use ShowEnsemble;

    public function index()
    {
        if (Auth::user()->role == UserRole::Ensemble) {
            return $this->show(Auth::user()->ensembles()->first());
        }

        return view('dashboard.index',
            ['page_name' => config('Dashboard')]
        );
    }
}
