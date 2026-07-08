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

/*
| Attendance polls are guarded by policies rather than the auth middleware so
| the shared "Ensemble" login can reach them.
*/
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

/*
| The seating-plan PDF authorises inside the controller, so it stays outside
| the auth group (kept alongside the other seating-plan route names).
*/
Route::get('/ensembles/{ensemble}/seating-plan/{termDate}/download', [SeatingPlanController::class, 'download'])
    ->name('ensembles.seating-plan.download')
    ->withoutScopedBindings();

Route::middleware('auth')->group(function () {
    /*
    | Soft-deletable resources each expose the standard resource routes plus a
    | restore route and (where the controller supports it) a purge route.
    */
    $softDeletableResources = [
        'composers' => ComposerController::class,
        'ensembles' => EnsembleController::class,
        'pieces' => PieceController::class,
        'setlists' => SetlistController::class,
        'terms' => TermController::class,
        'users' => UserController::class,
    ];

    foreach ($softDeletableResources as $name => $controller) {
        Route::patch("{$name}/{id}/restore", [$controller, 'restore'])->name("{$name}.restore");

        if (method_exists($controller, 'purgeTrashed')) {
            Route::delete("{$name}/purge", [$controller, 'purgeTrashed'])->name("{$name}.purge");
        }

        Route::resource($name, $controller);
    }

    // Setup groups follow the same pattern but bind their route parameter to $setupGroup.
    Route::patch('setup-groups/{id}/restore', [SetupGroupController::class, 'restore'])->name('setup-groups.restore');
    Route::delete('setup-groups/purge', [SetupGroupController::class, 'purgeTrashed'])->name('setup-groups.purge');
    Route::resource('setup-groups', SetupGroupController::class)->parameter('setup-groups', 'setupGroup');

    // Ensemble membership and seating plan (the PDF download is defined above).
    Route::prefix('ensembles/{ensemble}')->name('ensembles.')->group(function () {
        Route::get('members', [EnsembleController::class, 'members'])->name('members');
        Route::post('add-user', [EnsembleController::class, 'add_user'])->name('add-user');
        Route::post('remove-user/{user}', [EnsembleController::class, 'remove_user'])->name('remove-user');
        Route::get('seating-plan', [SeatingPlanController::class, 'show'])->name('seating-plan.show');
        Route::post('seating-plan', [SeatingPlanController::class, 'update'])->name('seating-plan.update');
    });

    // Term-date notifications.
    Route::post('/term-dates/{termDate}/send-attendance-list', [TermDateNotificationController::class, 'sendAttendanceList'])
        ->name('term-dates.send-attendance-list');
    Route::post('/term-dates/{termDate}/send-setup-reminder', [TermDateNotificationController::class, 'sendSetupReminder'])
        ->name('term-dates.send-setup-reminder');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
});

require __DIR__.'/auth.php';
