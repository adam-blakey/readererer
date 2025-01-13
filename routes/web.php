<?php

use App\Http\Controllers\PieceController;
use App\Http\Controllers\EnsembleController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ComposerController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Models\Piece;
use App\Models\Composer;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\Attendance;
use App\Models\User;

Route::view('/', 'home', ['page_name' => config('app.name')])
    ->name('home');
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->can('view.dashboard');

Route::get('/pieces', [PieceController::class, 'index'])
    ->name('pieces')
    ->can('viewAny', Piece::class);
Route::get('/pieces/{piece}', [PieceController::class, 'show'])
    ->name('pieces.show')
    ->can('view', 'piece');

Route::get('/composers', [ComposerController::class, 'index'])
    ->name('composers')
    ->can('viewAny', Composer::class);
Route::get('/composers/{composer}', [ComposerController::class, 'show'])
    ->name('composers.show')
    ->can('view', 'composer');

Route::middleware('auth')->group(function () {
    Route::get('/ensembles', [EnsembleController::class, 'index'])
        ->name('ensembles')
        ->can('viewAny', Ensemble::class);
    Route::get('/ensembles/{ensemble}', [EnsembleController::class, 'show'])
        ->name('ensembles.show')
        ->can('view', 'ensemble');
    Route::get('/ensembles/{ensemble}/edit', [EnsembleController::class, 'edit'])
        ->name('ensembles.edit')
        ->can('update', 'ensemble');
});

Route::get('/terms', [TermController::class, 'index'])
    ->name('terms')
    ->can('viewAny', Term::class);
Route::get('/terms/{term}', [TermController::class, 'show'])
    ->name('terms.show')
    ->can('view', 'term');

Route::get('/attendance', [AttendanceController::class, 'index'])
    ->name('attendance')
    ->can('viewAny', Attendance::class);
Route::get('/attendance/poll/{ensemble:slug}/{term:slug}', [AttendanceController::class, 'poll'])
    ->withoutScopedBindings()
    ->name('attendance.poll')
    ->can('poll', Attendance::class);

Route::get('/users', [UserController::class, 'index'])
    ->name('users')
    ->can('viewAny', User::class);
Route::get('/users/{user}/edit', [UserController::class, 'edit'])
    ->name('users.edit')
    ->can('update', 'user');
Route::get('/users/{user}', [UserController::class, 'show'])
    ->name('users.show')
    ->can('view', 'user');

require __DIR__.'/auth.php';