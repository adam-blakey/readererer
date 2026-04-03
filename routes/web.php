<?php

use App\Http\Controllers\PieceController;
use App\Http\Controllers\EnsembleController;
use App\Http\Controllers\SeatingPlanController;
use App\Http\Controllers\SeatingPlanPdfController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ComposerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SetlistController;
use App\Http\Controllers\SetupGroupController;
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
    ->can('view', 'ensemble');
Route::post('/attendance/poll/{ensemble:slug}/{term:slug}', [AttendanceController::class, 'poll_store'])
    ->withoutScopedBindings()
    ->name('attendance.poll-store')
    ->can('create', Attendance::class);

Route::resource('composers', ComposerController::class)->middleware('auth');
Route::patch('/composers/{composer}/restore', [ComposerController::class, 'restore'])->name('composers.restore')->middleware('auth');
Route::resource('ensembles', EnsembleController::class)->middleware('auth');
Route::patch('/ensembles/{ensemble}/restore', [EnsembleController::class, 'restore'])->name('ensembles.restore')->middleware('auth');
Route::post('/ensembles/{ensemble}/add_user', [EnsembleController::class, 'add_user'])->name('ensembles.add_user')->middleware('auth');
Route::post('/ensembles/{ensemble}/remove_user/{user}', [EnsembleController::class, 'remove_user'])->name('ensembles.remove_user')->middleware('auth');
Route::get('/ensembles/{ensemble}/seating-plan', [SeatingPlanController::class, 'show'])->name('ensembles.seating-plan.show')->middleware('auth');
Route::post('/ensembles/{ensemble}/seating-plan', [SeatingPlanController::class, 'update'])->name('ensembles.seating-plan.update')->middleware('auth');
Route::get('/ensembles/{ensemble}/seating-plan/{termDate:id}/download', [SeatingPlanController::class, 'download'])->name('seating-plan.download')->withoutScopedBindings();
Route::resource('pieces', PieceController::class)->middleware('auth');
Route::patch('/pieces/{piece}/restore', [PieceController::class, 'restore'])->name('pieces.restore')->middleware('auth');
Route::resource('setlists', SetlistController::class)->middleware('auth');
Route::patch('/setlists/{setlist}/restore', [SetlistController::class, 'restore'])->name('setlists.restore')->middleware('auth');
Route::resource('terms', TermController::class)->middleware('auth');
Route::patch('/terms/{term}/restore', [TermController::class, 'restore'])->name('terms.restore')->middleware('auth');
Route::resource('users', UserController::class)->middleware('auth');
Route::patch('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore')->middleware('auth');
Route::resource('setupgroups', SetupGroupController::class)->middleware('auth')->parameter('setupgroups', 'setupGroup');
Route::patch('/setupgroups/{setupgroup}/restore', [SetupGroupController::class, 'restore'])->name('setupgroups.restore')->middleware('auth');


require __DIR__.'/auth.php';
