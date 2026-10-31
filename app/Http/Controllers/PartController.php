<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartRequest;
use App\Http\Requests\UpdatePartRequest;
use App\Models\Part;

class PartController extends Controller
{
    public function index() {}

    public function create() {}

    public function store(StorePartRequest $request) {}

    public function show(Part $parts) {}

    public function edit(Part $parts) {}

    public function update(UpdatePartRequest $request, Part $parts) {}

    public function destroy(Part $parts) {}
}
