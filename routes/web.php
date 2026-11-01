<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRegisterController;
use App\Http\Controllers\ComposerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\EnsembleController;
use App\Http\Controllers\HealthCheckController;
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
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthCheckController::class)
    ->name('health');

Route::post('/_deploy', DeployController::class)
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->middleware('throttle:5,1');

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
    ->name('attendance.poll.show')
    ->can('view', 'ensemble');
Route::post('/attendance/poll/{ensemble:slug}/{term:slug}', [AttendanceController::class, 'poll_store'])
    ->withoutScopedBindings()
    ->name('attendance.poll.store')
    ->can('view', 'ensemble');

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

Route::get('/ensembles/{ensemble}/seating-plan/{termDate}/download', [SeatingPlanController::class, 'download'])
    ->name('ensembles.seating-plan.download')
    ->withoutScopedBindings();

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index')
        ->can('view.notifications');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');

    Route::patch('/composers/{id}/restore', [ComposerController::class, 'restore'])->name('composers.restore');
    Route::resource('composers', ComposerController::class);

    Route::patch('/ensembles/{id}/restore', [EnsembleController::class, 'restore'])->name('ensembles.restore');
    Route::resource('ensembles', EnsembleController::class);
    Route::get('/ensembles/{ensemble}/members', [EnsembleController::class, 'members'])->name('ensembles.members.index');
    Route::post('/ensembles/{ensemble}/members', [EnsembleController::class, 'addMember'])->name('ensembles.members.store');
    Route::delete('/ensembles/{ensemble}/members/{user}', [EnsembleController::class, 'removeMember'])->name('ensembles.members.destroy');
    Route::get('/ensembles/{ensemble}/seating-plan', [SeatingPlanController::class, 'show'])->name('ensembles.seating-plan.show');
    Route::put('/ensembles/{ensemble}/seating-plan', [SeatingPlanController::class, 'update'])->name('ensembles.seating-plan.update');

    Route::patch('/instrument-families/{id}/restore', [InstrumentFamilyController::class, 'restore'])->name('instrumentfamilys.restore');
    Route::resource('instrument-families', InstrumentFamilyController::class)
        ->names('instrumentfamilys')
        ->parameter('instrument-families', 'instrumentFamily');

    Route::patch('/pieces/{id}/restore', [PieceController::class, 'restore'])->name('pieces.restore');
    Route::resource('pieces', PieceController::class);

    Route::patch('/setlists/{id}/restore', [SetlistController::class, 'restore'])->name('setlists.restore');
    Route::resource('setlists', SetlistController::class);

    Route::patch('/setup-groups/{id}/restore', [SetupGroupController::class, 'restore'])->name('setupgroups.restore');
    Route::resource('setup-groups', SetupGroupController::class)
        ->names('setupgroups')
        ->parameter('setup-groups', 'setupGroup');

    Route::patch('/terms/{id}/restore', [TermController::class, 'restore'])->name('terms.restore');
    Route::resource('terms', TermController::class);

    Route::post('/term-dates/{termDate}/send-attendance-list', [TermDateNotificationController::class, 'sendAttendanceList'])->name('term-dates.send-attendance-list');
    Route::post('/term-dates/{termDate}/send-setup-reminder', [TermDateNotificationController::class, 'sendSetupReminder'])->name('term-dates.send-setup-reminder');
    Route::post('/term-dates/{termDate}/send-van-driver-reminder', [TermDateNotificationController::class, 'sendVanDriverReminder'])->name('term-dates.send-van-driver-reminder');

    Route::patch('/users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::resource('users', UserController::class);
    Route::post('/users/{user}/ensembles', [UserController::class, 'attachEnsemble'])->name('users.ensembles.store');
    Route::delete('/users/{user}/ensembles/{ensemble}', [UserController::class, 'detachEnsemble'])->name('users.ensembles.destroy');
});

require __DIR__.'/auth.php';
