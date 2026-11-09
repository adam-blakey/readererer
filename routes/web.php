<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRegisterController;
use App\Http\Controllers\ComposerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnsembleController;
use App\Http\Controllers\FeatureFlagController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstrumentFamilyController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PieceController;
use App\Http\Controllers\PollererImportController;
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

Route::get('/health', HealthCheckController::class)
    ->name('health');

Route::get('/', HomeController::class)
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
    ->can('create', [Attendance::class, 'ensemble']);
Route::put('/attendance/poll/view', [AttendanceController::class, 'poll_view'])
    ->name('attendance.poll.view')
    ->middleware('auth');
Route::get('/attendance/poll/{ensemble:slug}/{term:slug}/history/{user}', [AttendanceController::class, 'history'])
    ->withoutScopedBindings()
    ->name('attendance.poll.history')
    ->can('view', 'ensemble');

Route::get('/attendance/register', [AttendanceRegisterController::class, 'index'])
    ->name('attendance.register.index')
    ->can('viewAny', RegisterEntry::class);
Route::get('/attendance/register/{ensemble:slug}/{termDate}', [AttendanceRegisterController::class, 'show'])
    ->withoutScopedBindings()
    ->name('attendance.register.show')
    ->can('viewAny', RegisterEntry::class)
    ->can('view', 'ensemble');
Route::post('/attendance/register/{ensemble:slug}/{termDate}', [AttendanceRegisterController::class, 'store'])
    ->withoutScopedBindings()
    ->name('attendance.register.store')
    ->can('create', RegisterEntry::class)
    ->can('view', 'ensemble');

Route::get('/ensembles/{ensemble}/seating-plan/{termDate}/download', [SeatingPlanController::class, 'download'])
    ->name('ensembles.seating-plan.download')
    ->withoutScopedBindings()
    ->can('view', 'ensemble');

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index')
        ->can('view.notifications');
    Route::post('/notifications/{emailLog}/resend', [NotificationController::class, 'resend'])
        ->name('notifications.resend')
        ->can('send.notifications');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit')->can('view.settings');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update')->can('edit.settings');
    Route::get('/settings/monitoring', [MonitoringController::class, 'index'])->name('settings.monitoring')->can('edit.settings');
    Route::get('/settings/monitoring/logs', [MonitoringController::class, 'logs'])->name('settings.monitoring.logs')->can('edit.settings');
    Route::get('/settings/import', [PollererImportController::class, 'create'])->name('settings.import')->can('edit.settings');
    Route::post('/settings/import/preview', [PollererImportController::class, 'preview'])->name('settings.import.preview')->can('edit.settings');
    Route::post('/settings/import', [PollererImportController::class, 'store'])->name('settings.import.store')->can('edit.settings');

    Route::get('/feature-flags', [FeatureFlagController::class, 'edit'])->name('feature-flags.edit')->can('edit.settings');
    Route::put('/feature-flags', [FeatureFlagController::class, 'update'])->name('feature-flags.update')->can('edit.settings');

    Route::patch('/composers/{composer}/restore', [ComposerController::class, 'restore'])->name('composers.restore')->withTrashed()->can('restore', 'composer');
    Route::resource('composers', ComposerController::class)->only(['index', 'destroy']);

    Route::patch('/ensembles/{ensemble}/restore', [EnsembleController::class, 'restore'])->name('ensembles.restore')->withTrashed()->can('restore', 'ensemble');
    Route::resource('ensembles', EnsembleController::class);
    Route::get('/ensembles/{ensemble}/members', [EnsembleController::class, 'members'])->name('ensembles.members.index')->can('view', 'ensemble');
    Route::post('/ensembles/{ensemble}/members', [EnsembleController::class, 'addMember'])->name('ensembles.members.store')->can('update', 'ensemble');
    Route::delete('/ensembles/{ensemble}/members/{user}', [EnsembleController::class, 'removeMember'])->name('ensembles.members.destroy')->can('update', 'ensemble');
    Route::get('/ensembles/{ensemble}/seating-plan', [SeatingPlanController::class, 'show'])->name('ensembles.seating-plan.show')->can('update', 'ensemble');
    Route::put('/ensembles/{ensemble}/seating-plan', [SeatingPlanController::class, 'update'])->name('ensembles.seating-plan.update')->can('update', 'ensemble');

    Route::patch('/instrument-families/{instrumentFamily}/restore', [InstrumentFamilyController::class, 'restore'])->name('instrumentfamilys.restore')->withTrashed()->can('restore', 'instrumentFamily');
    Route::resource('instrument-families', InstrumentFamilyController::class)
        ->names('instrumentfamilys')
        ->parameter('instrument-families', 'instrumentFamily');

    Route::patch('/locations/{location}/restore', [LocationController::class, 'restore'])->name('locations.restore')->withTrashed()->can('restore', 'location');
    Route::resource('locations', LocationController::class);

    Route::patch('/pieces/{piece}/restore', [PieceController::class, 'restore'])->name('pieces.restore')->withTrashed()->can('restore', 'piece');
    Route::resource('pieces', PieceController::class)->only(['index', 'show', 'destroy']);

    Route::patch('/setlists/{setlist}/restore', [SetlistController::class, 'restore'])->name('setlists.restore')->withTrashed()->can('restore', 'setlist');
    Route::resource('setlists', SetlistController::class)->only(['index', 'destroy']);

    Route::patch('/setup-groups/{setupGroup}/restore', [SetupGroupController::class, 'restore'])->name('setupgroups.restore')->withTrashed()->can('restore', 'setupGroup');
    Route::resource('setup-groups', SetupGroupController::class)
        ->names('setupgroups')
        ->parameter('setup-groups', 'setupGroup');

    Route::patch('/terms/{term}/restore', [TermController::class, 'restore'])->name('terms.restore')->withTrashed()->can('restore', 'term');
    Route::resource('terms', TermController::class);

    Route::post('/term-dates/{termDate}/send-attendance-list', [TermDateNotificationController::class, 'sendAttendanceList'])->name('term-dates.send-attendance-list')->can('sendNotifications', 'termDate');
    Route::post('/term-dates/{termDate}/send-setup-reminder', [TermDateNotificationController::class, 'sendSetupReminder'])->name('term-dates.send-setup-reminder')->can('sendNotifications', 'termDate');
    Route::post('/term-dates/{termDate}/send-van-driver-reminder', [TermDateNotificationController::class, 'sendVanDriverReminder'])->name('term-dates.send-van-driver-reminder')->can('sendNotifications', 'termDate');

    Route::patch('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore')->withTrashed()->can('restore', 'user');
    Route::resource('users', UserController::class)->withTrashed(['show']);
    Route::delete('/users/{user}/erase', [UserController::class, 'erase'])->name('users.erase')->withTrashed()->can('erase', 'user');
    Route::post('/users/{user}/password-reset-link', [UserController::class, 'sendPasswordResetLink'])->name('users.password-reset-link')->can('resetPassword', 'user');
    Route::put('/users/{user}/password', [UserController::class, 'setPassword'])->name('users.password')->can('resetPassword', 'user');
    Route::post('/users/{user}/ensembles', [UserController::class, 'attachEnsemble'])->name('users.ensembles.store')->can('manage', 'user');
    Route::delete('/users/{user}/ensembles/{ensemble}', [UserController::class, 'detachEnsemble'])->name('users.ensembles.destroy')->can('manage', 'user');
});

require __DIR__.'/auth.php';
