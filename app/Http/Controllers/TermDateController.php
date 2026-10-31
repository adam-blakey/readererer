<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTermDateRequest;
use App\Http\Requests\UpdateTermDateRequest;
use App\Models\TermDate;

class TermDateController extends Controller
{
    public function index() {}

    public function create() {}

    public function store(StoreTermDateRequest $request) {}

    public function show(TermDate $termDate) {}

    public function edit(TermDate $termDate) {}

    public function update(UpdateTermDateRequest $request, TermDate $termDate) {}

    public function destroy(TermDate $termDate) {}
}
