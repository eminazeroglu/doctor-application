<?php

use App\Http\Controllers\Api\Admin\ReferenceDataController;
use Illuminate\Support\Facades\Route;

/*
 * Enum
 * */
Route::controller(ReferenceDataController::class)->group(function () {
    Route::get('/permissions', 'permissions')->middleware(['auth:sanctum', 'auth:admin_control'])->name('permissions');
    Route::get('/genders', 'genders')->name('genders');
    Route::get('/search/doctor-or-service', 'doctorOrService')->name('doctorOrService');
    Route::get('/home', 'home')->name('home');
    Route::get('/time-of-day', 'timeOfDay')->name('timeOfDay');
    Route::get('/languages', 'languages')->name('languages');
    Route::get('/languages/{locale}/translates', 'languageWithTranslates')->name('languageWithTranslates');
    Route::get('/system/image-watermark-positions', 'systemImageWatermarkPosition')->name('systemImageWatermarkPosition');
    Route::get('/services', 'services')->name('services');
    Route::get('/attributes', 'attributes')->name('attributes');
    Route::get('/attribute-types', 'attributeTypes')->name('attributeTypes');
    Route::get('/attribute-positions', 'attributePositions')->name('attributePositions');
    Route::get('/attribute/{id}/options', 'attributeOptions')->name('attribute.options');
    Route::get('/attribute/option/{parentOptionId}/dependent-options', 'dependentOptions')->name('dependent-options');
    Route::get('/categories', 'categories')->name('categories');
    Route::get('/categories/{parentId}/children', 'categoryChildren')->name('category-children');
    Route::get('/category/{id}/attributes', 'categoryAttributes')->name('category.attributes');
    Route::get('/payment-service/{type}', 'paymentService')->name('paymentService');
    Route::get('/countries', 'countries')->name('countries');
    Route::get('/countries/{uuid}/cities', 'countryWithCities')->name('countryWithCities');
    Route::get('/cities', 'cities')->name('cities');
    Route::get('/cities/{uuid}/regions', 'cityWithRegions')->name('cityWithRegions');
    Route::get('/cities/{uuid}/regions', 'cityWithRegions')->name('cityWithRegions');
    Route::get('/cities/{uuid}/subways', 'cityWithSubways')->name('cityWithSubways');
    Route::get('/regions', 'regions')->name('regions');
    Route::get('/regions/{uuid}/subways', 'regionWithSubways')->name('regionWithSubways');
    Route::get('/regions/{uuid}/subways', 'regionWithSubways')->name('regionWithSubways');
    Route::get('/clinics', 'clinics')->name('clinics');
    Route::get('/appointment-cancel-reasons/{type}', 'appointmentCancelReasons')->name('appointmentCancelReasons');
});
