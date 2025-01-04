<?php

use App\Http\Controllers\PieceController;
use App\Http\Controllers\EnsembleController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PieceController::class, 'index'])
    ->name('home');
Route::get('/dashboard',[PieceController::class, 'index'])
    ->name('dashboard');

Route::get('/pieces',[PieceController::class, 'index'])
    ->name('pieces');
Route::get('/pieces/{piece}',[PieceController::class, 'show'])
    ->name('pieces.show');

Route::get('/composers',[PieceController::class, 'index'])
    ->name('composers');
Route::get('/composers/{composer}',[PieceController::class, 'show'])
    ->name('composers.show');

Route::middleware('auth')->group(function () {
    Route::get('/ensembles',[EnsembleController::class, 'index'])
        ->name('ensembles');
    Route::get('/ensembles/{ensemble}',[EnsembleController::class, 'show'])
        ->name('ensembles.show');
    Route::get('/ensembles/{ensemble}/edit',[EnsembleController::class, 'edit'])
        ->name('ensembles.edit');
});

Route::get('/terms', [TermController::class, 'index'])
    ->name('terms');
Route::get('/terms/{term}', [TermController::class, 'show'])
    ->name('terms.show');

Route::get('/attendance', [AttendanceController::class, 'index'])
    ->name('attendance');
Route::get('/attendance/poll/{ensemble}/{term}', [AttendanceController::class, 'poll'])
    ->name('attendance.poll');
Route::get('/attendance/poll/{ensemble:slug}/{term:slug}', [AttendanceController::class, 'poll_slug'])
    ->name('attendance.poll_slug');

Route::get('/users', [UserController::class, 'index'])
    ->name('users');
Route::get('/users/{id}', [UserController::class, 'edit'])
    ->name('users.edit');

require __DIR__.'/auth.php';