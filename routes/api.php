<?php

use App\Http\Controllers\Api\Admin\SeoLinkController;
use App\Http\Controllers\DocController;
use App\Http\Controllers\Telegram\TelegramWebhookController;
use Illuminate\Support\Facades\Route;


Route::get('/documentation', [DocController::class, 'index']);
Route::get('/documentation/endpoints', [DocController::class, 'getApiDocs']);

Route::prefix('telegram')->group(function () {
    Route::post('webhook', [TelegramWebhookController::class, 'handle']);
    Route::post('webhook/set', [TelegramWebhookController::class, 'setWebhook']);
    Route::post('webhook/remove', [TelegramWebhookController::class, 'removeWebhook']);
    Route::get('webhook/info', [TelegramWebhookController::class, 'getWebhookInfo']);
});

Route::prefix('seo')->group(function () {
    Route::get('/sitemap.xml', [SeoLinkController::class, 'sitemap']);
    Route::get('/sitemap/{type}.xml', [SeoLinkController::class, 'sitemapByType']);
});
