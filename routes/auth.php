<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('inscription', [RegisteredUserController::class, 'create'])
        ->name('inscription');

    Route::post('inscription', [RegisteredUserController::class, 'store']);

    // Routes pour la sélection de rôle après inscription
    Route::get('selection-role', [RegisteredUserController::class, 'roleSelection'])
        ->name('role.selection');
    
    Route::post('selection-role', [RegisteredUserController::class, 'completeRegistration'])
        ->name('role.complete');

    Route::get('connexion', [AuthenticatedSessionController::class, 'create'])
        ->name('connexion');

    Route::post('connexion', [AuthenticatedSessionController::class, 'store']);

    Route::get('mot-de-passe-oublie', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('mot-de-passe-oublie', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reinitialiser-mot-de-passe/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reinitialiser-mot-de-passe', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verifier-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verifier-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/notification-verification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirmer-mot-de-passe', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirmer-mot-de-passe', [ConfirmablePasswordController::class, 'store']);

    Route::post('deconnexion', [AuthenticatedSessionController::class, 'destroy'])
        ->name('deconnexion');
});
