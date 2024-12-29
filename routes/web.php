<?php

use App\Http\Controllers\PieceController;
use App\Http\Controllers\EnsembleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PieceController::class, 'index']);
Route::get('/pieces',[PieceController::class, 'index']);
Route::get('/ensembles',[EnsembleController::class, 'index']);