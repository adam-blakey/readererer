<?php

use App\Http\Controllers\PieceController;
use App\Http\Controllers\EnsembleController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ComposerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SetlistController;
use Illuminate\Support\Facades\Route;
use App\Models\Piece;
use App\Models\Composer;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Setlist;

Route::view('/', 'home', ['page_name' => config('app.name')])
    ->name('home');
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->can('view.dashboard');

Route::get('/attendance', [AttendanceController::class, 'index'])
    ->name('attendance.index')
    ->can('viewAny', Attendance::class);
Route::get('/attendance/poll/{ensemble:slug}/{term:slug}', [AttendanceController::class, 'poll'])
    ->withoutScopedBindings()
    ->name('attendance.poll')
    ->can('poll', Attendance::class);
Route::post('/attendance/poll/{ensemble:slug}/{term:slug}', [AttendanceController::class, 'poll_store'])
    ->withoutScopedBindings()
    ->name('attendance.poll-store')
    ->can('create', Attendance::class);

Route::resource('composers', ComposerController::class)->middleware('auth');;
Route::resource('ensembles', EnsembleController::class)->middleware('auth');
Route::resource('pieces', PieceController::class)->middleware('auth');;
Route::resource('setlists', SetlistController::class)->middleware('auth');;
Route::resource('terms', TermController::class)->middleware('auth');;
Route::resource('users', UserController::class)->middleware('auth');;

require __DIR__.'/auth.php';
