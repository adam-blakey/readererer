<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnsembleAdminRequest;
use App\Http\Requests\UpdateEnsembleAdminRequest;
use App\Models\EnsembleAdmin;

class EnsembleAdminController extends Controller
{
    public function index() {}

    public function create() {}

    public function store(StoreEnsembleAdminRequest $request) {}

    public function show(EnsembleAdmin $ensembleAdmin) {}

    public function edit(EnsembleAdmin $ensembleAdmin) {}

    public function update(UpdateEnsembleAdminRequest $request, EnsembleAdmin $ensembleAdmin) {}

    public function destroy(EnsembleAdmin $ensembleAdmin) {}
}
