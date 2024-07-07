<?php

use App\Http\Controllers\PieceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PieceController::class, 'index']);
