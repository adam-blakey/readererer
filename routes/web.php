<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ComposerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnsembleController;
use App\Http\Controllers\PieceController;
use App\Http\Controllers\SeatingPlanController;
use App\Http\Controllers\SetlistController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SetupGroupController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\TermDateNotificationController;
use App\Http\Controllers\UserController;
use App\Models\Attendance;
use Illuminate\Support\Facades\Route;

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
Route::get('/ensembles/{ensemble}/members', [EnsembleController::class, 'members'])->name('ensembles.members')->middleware('auth');
Route::patch('/ensembles/{ensemble}/seating-plan-enabled', [EnsembleController::class, 'updateSeatingPlanEnabled'])->name('ensembles.seating-plan-enabled')->middleware('auth');
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
Route::post('/term-dates/{termDate}/send-attendance-list', [TermDateNotificationController::class, 'sendAttendanceList'])->name('term-dates.send-attendance-list')->middleware('auth');
Route::post('/term-dates/{termDate}/send-setup-reminder', [TermDateNotificationController::class, 'sendSetupReminder'])->name('term-dates.send-setup-reminder')->middleware('auth');
Route::resource('users', UserController::class)->middleware('auth');
Route::patch('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore')->middleware('auth');
Route::post('/users/{user}/ensembles', [UserController::class, 'attachEnsemble'])->name('users.ensembles.attach')->middleware('auth');
Route::delete('/users/{user}/ensembles/{ensemble}', [UserController::class, 'detachEnsemble'])->name('users.ensembles.detach')->middleware('auth');
Route::resource('setupgroups', SetupGroupController::class)->middleware('auth')->parameter('setupgroups', 'setupGroup');
Route::patch('/setupgroups/{setupgroup}/restore', [SetupGroupController::class, 'restore'])->name('setupgroups.restore')->middleware('auth');
Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit')->middleware('auth');

require __DIR__.'/auth.php';
