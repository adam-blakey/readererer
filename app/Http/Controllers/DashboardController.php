<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index',
            ['page_name' => config('Dashboard')]
        );
    }
}