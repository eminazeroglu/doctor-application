<?php

use App\Http\Controllers\Api\Admin\CommonController;
use App\Http\Controllers\Api\Admin\LanguageController;
use App\Http\Controllers\Api\Admin\ProfileController;
use App\Http\Controllers\Api\Admin\TerminalController;
use Illuminate\Support\Facades\Route;

/**
 * Common
 */
Route::controller(CommonController::class)->group(function () {
    Route::get('/start', 'start');
});

Route::middleware('auth.user_control')->group(function () {
    /*
    * User
    * */
    Route::controller(ProfileController::class)->middleware('auth:sanctum')->group(function () {
        Route::get('/user/profile', 'getProfile');
        Route::put('/user/profile', 'updateProfile');
        Route::put('/user/password', 'updatePassword');
        Route::put('/user/email', 'updateEmail');
        Route::get('/user/preferences', 'getPreferences');
        Route::put('/user/preferences', 'updatePreferences');
    });

    /*
    * Language
    * */
    Route::controller(LanguageController::class)->middleware('auth:sanctum')->group(function () {
        Route::get('languages/{locale}/translations', 'translations');
        Route::post('languages/{locale}/translations', 'updateTranslation');
        Route::get('languages/current-language', 'currentLanguage');
    });

    Route::controller(TerminalController::class)
        ->prefix('terminal')
        ->middleware(['auth:sanctum'])
        ->group(function () {
            Route::get('/commands', 'getCommands'); // Komandaları əldə etmək üçün
            Route::post('/execute', 'execute'); // Komandanı icra etmək üçün
        });
});
