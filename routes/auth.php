<?php

use App\Features\TwoFactorAuthentication;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\Auth\LoginLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('auth/google', [GoogleLoginController::class, 'redirect'])
        ->name('google.redirect');

    Route::get('auth/google/callback', [GoogleLoginController::class, 'callback'])
        ->name('google.callback');

    Route::get('login/email', [LoginLinkController::class, 'create'])
        ->name('login-link.request');

    Route::post('login/email', [LoginLinkController::class, 'store'])
        ->middleware('throttle:6,1,login-link.email')
        ->name('login-link.email');

    Route::get('login/email/{token}', [LoginLinkController::class, 'show'])
        ->name('login-link.show');

    Route::post('login/email/{token}', [LoginLinkController::class, 'login'])
        ->middleware('throttle:6,1,login-link.login')
        ->name('login-link.login');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1,password.email')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1,password.store')
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1,verification.verify'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1,verification.send')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])
        ->middleware('throttle:6,1,password.confirm');

    Route::get('password', [PasswordController::class, 'edit'])
        ->name('password.edit');

    Route::put('password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1,password.update')
        ->name('password.update');

    Route::get('two-factor/challenge', [TwoFactorController::class, 'challenge'])
        ->name('two-factor.challenge');

    Route::post('two-factor/challenge', [TwoFactorController::class, 'verify'])
        ->middleware('throttle:5,1,two-factor.verify')
        ->name('two-factor.verify');

    Route::middleware('can:useTwoFactor,'.User::class)->group(function () {
        Route::get('two-factor', [TwoFactorController::class, 'edit'])
            ->name('two-factor.edit');

        Route::post('two-factor', [TwoFactorController::class, 'store'])
            ->middleware(['throttle:6,1,two-factor.store', EnsureFeaturesAreActive::using(TwoFactorAuthentication::class)])
            ->name('two-factor.store');

        Route::delete('two-factor', [TwoFactorController::class, 'destroy'])
            ->middleware('throttle:6,1,two-factor.destroy')
            ->name('two-factor.destroy');
    });

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
