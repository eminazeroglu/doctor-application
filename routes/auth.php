<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\SocialLoginController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register')->middleware('check.blocked');
    Route::post('login', 'login')->middleware('check.blocked');
    Route::post('logout', 'logout')->middleware('auth:sanctum');
    Route::get('user', 'user')->middleware('auth:sanctum');
    Route::post('refresh', 'refresh')->middleware('auth:sanctum');
    Route::post('forgot-password', 'forgotPassword')->middleware('check.blocked');
    Route::post('reset-password', 'resetPassword');
    Route::post('email/verify', 'verifyEmail');
    Route::post('email/resend', 'resendVerificationEmail')->middleware('auth:sanctum');
    Route::get('login-with-token/{token}', 'loginWithToken')->middleware('auth:sanctum');

    Route::prefix('social')->group(function () {
        Route::post('redirect', [SocialLoginController::class, 'redirect'])
            ->middleware('check.blocked');

        Route::post('callback', [SocialLoginController::class, 'callback'])
            ->middleware('check.blocked');
    });
});
