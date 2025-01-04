<?php

use App\Http\Controllers\PieceController;
use App\Http\Controllers\EnsembleController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PieceController::class, 'index']);
Route::get('/dashboard',[PieceController::class, 'index'])->name('dashboard');
Route::get('/pieces',[PieceController::class, 'index']);

Route::middleware('auth')->group(function () {
    Route::get('/ensembles',[EnsembleController::class, 'index']);
    Route::get('/ensembles/{ensemble}',[EnsembleController::class, 'show']);
    Route::get('/ensembles/{ensemble}/edit',[EnsembleController::class, 'edit']);
});

Route::get('/terms', [TermController::class, 'index']);

Route::get('/attendance', [AttendanceController::class, 'index']);
Route::get('/attendance/poll/{ensemble}/{term}', [AttendanceController::class, 'poll']);
Route::get('/attendance/poll/{ensemble:slug}/{term:slug}', [AttendanceController::class, 'poll_slug']);

Route::get('/users', [UserController::class, 'index']);

require __DIR__.'/auth.php';