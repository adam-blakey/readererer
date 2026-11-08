<?php

namespace App\Http\Controllers;

use App\Models\Ensemble;
use App\Models\Term;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', [
            'ensembles' => Ensemble::orderBy('name')->get(),
            'terms' => Term::current()->visibleTo(auth()->user())->with('term_dates')->get()->sortBy('latest_date'),
            'page_name' => config('app.name'),
        ]);
    }
}
