<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRegisterController;
use App\Http\Controllers\ComposerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnsembleController;
use App\Http\Controllers\InstrumentFamilyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PieceController;
use App\Http\Controllers\SeatingPlanController;
use App\Http\Controllers\SetlistController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SetupGroupController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\TermDateNotificationController;
use App\Http\Controllers\UserController;
use App\Models\Attendance;
use App\Models\RegisterEntry;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home', ['page_name' => config('app.name')])
    ->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->can('view.dashboard');

/*
| Attendance polls and registers are guarded by policies rather than the auth
| middleware so the shared "Ensemble" login can reach them.
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

Route::get('/attendance/register', [AttendanceRegisterController::class, 'index'])
    ->name('attendance.register.index')
    ->can('viewAny', RegisterEntry::class);
Route::get('/attendance/register/{ensemble:slug}/{termDate}', [AttendanceRegisterController::class, 'show'])
    ->withoutScopedBindings()
    ->name('attendance.register.show')
    ->can('viewAny', RegisterEntry::class);
Route::post('/attendance/register/{ensemble:slug}/{termDate}', [AttendanceRegisterController::class, 'store'])
    ->withoutScopedBindings()
    ->name('attendance.register.store')
    ->can('create', RegisterEntry::class);

/*
| The seating-plan PDF authorises inside the controller, so it stays outside
| the auth group (kept alongside the other seating-plan route names).
*/
Route::get('/ensembles/{ensemble}/seating-plan/{termDate}/download', [SeatingPlanController::class, 'download'])
    ->name('ensembles.seating-plan.download')
    ->withoutScopedBindings();

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index')
        ->can('view.notifications');

    /*
    | Soft-deletable resources each expose the standard resource routes plus a
    | restore route and (where the controller supports it) a purge route. The
    | route *names* stay in the get_route_name_from_model() form so the shared
    | auto-entities views resolve them.
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

    /*
    | Instrument families and setup groups keep a clean kebab-case URL while
    | their route names stay in the get_route_name_from_model() form.
    */
    Route::patch('instrument-families/{id}/restore', [InstrumentFamilyController::class, 'restore'])->name('instrumentfamilys.restore');
    Route::delete('instrument-families/purge', [InstrumentFamilyController::class, 'purgeTrashed'])->name('instrumentfamilys.purge');
    Route::resource('instrument-families', InstrumentFamilyController::class)
        ->names('instrumentfamilys')
        ->parameter('instrument-families', 'instrumentFamily');

    Route::patch('setup-groups/{id}/restore', [SetupGroupController::class, 'restore'])->name('setupgroups.restore');
    Route::delete('setup-groups/purge', [SetupGroupController::class, 'purgeTrashed'])->name('setupgroups.purge');
    Route::resource('setup-groups', SetupGroupController::class)
        ->names('setupgroups')
        ->parameter('setup-groups', 'setupGroup');

    // Ensemble membership and seating plan (the PDF download is public, above).
    Route::prefix('ensembles/{ensemble}')->name('ensembles.')->group(function () {
        Route::get('members', [EnsembleController::class, 'members'])->name('members');
        Route::post('add-user', [EnsembleController::class, 'add_user'])->name('add-user');
        Route::post('remove-user/{user}', [EnsembleController::class, 'remove_user'])->name('remove-user');
        Route::get('seating-plan', [SeatingPlanController::class, 'show'])->name('seating-plan.show');
        Route::post('seating-plan', [SeatingPlanController::class, 'update'])->name('seating-plan.update');
    });

    // User <-> ensemble membership.
    Route::post('/users/{user}/ensembles', [UserController::class, 'attachEnsemble'])->name('users.ensembles.attach');
    Route::delete('/users/{user}/ensembles/{ensemble}', [UserController::class, 'detachEnsemble'])->name('users.ensembles.detach');

    // Term-date notifications.
    Route::post('/term-dates/{termDate}/send-attendance-list', [TermDateNotificationController::class, 'sendAttendanceList'])
        ->name('term-dates.send-attendance-list');
    Route::post('/term-dates/{termDate}/send-setup-reminder', [TermDateNotificationController::class, 'sendSetupReminder'])
        ->name('term-dates.send-setup-reminder');
    Route::post('/term-dates/{termDate}/send-van-driver-reminder', [TermDateNotificationController::class, 'sendVanDriverReminder'])
        ->name('term-dates.send-van-driver-reminder');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
});

require __DIR__.'/auth.php';
