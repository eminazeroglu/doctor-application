<?php

use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\SeoLinkToolsController;
use App\Http\Controllers\Api\Front\CategoryController;
use App\Http\Controllers\Api\Front\CommentController;
use App\Http\Controllers\Api\Front\ComplaintsController;
use App\Http\Controllers\Api\Front\MessagingController;
use App\Http\Controllers\Api\Front\PageController;
use App\Http\Controllers\Api\Front\UserBlockController;
use App\Http\Controllers\Api\Front\UserController;
use Illuminate\Support\Facades\Route;

/**
 * Seo Routes
 * */
Route::controller(SeoLinkToolsController::class)
    ->prefix('seo')
    ->group(function () {
        Route::get('sitemap.xml', 'sitemap');
        Route::get('sitemap-index.xml', 'sitemapIndex');
        Route::get('robots.txt', 'robots');
    });


/**
 * Pages Routes
 * */
Route::controller(PageController::class)->prefix('pages')->group(function () {
    Route::get('/', 'index');
    Route::get('/type/{type}', 'byType');
    Route::get('/{slug}', 'show');
});

/**
 * Notification Routes
 */
Route::controller(NotificationController::class)
    ->prefix('notifications')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/user', 'userNotifications');
        Route::post('/{id}/read', 'markAsRead');
        Route::post('/read-all', 'markAllAsRead');
        Route::get('/user/stats', 'userStats');
    });

/**
 * Complaints Routes
 * */
Route::controller(ComplaintsController::class)
    ->prefix('complaints')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        /**
         * Marşrutun məqsədi: İstifadəçinin öz şikayətlərini siyahıya alır.
         * GET /api/app/complaints/my endpointinə yönəldir.
         */
        Route::get('/', 'myComplaints')->name('complaints.my');

        /**
         * Marşrutun məqsədi: Yeni şikayət yaratmaq üçün POST sorğusu qəbul edir.
         * POST /api/app/complaints endpointinə yönəldir.
         */
        Route::post('/', 'store')->name('complaints.store');

        /**
         * Marşrutun məqsədi: Şikayətin detallarını göstərmək üçün GET sorğusu qəbul edir.
         * GET /api/app/complaints/{uuid} endpointinə yönəldir.
         */
        Route::get('/{uuid}', 'show')->name('complaints.show');

        /**
         * Marşrutun məqsədi: Şikayətə cavab yazmaq üçün POST sorğusu qəbul edir.
         * POST /api/app/complaints/{uuid}/reply endpointinə yönəldir.
         */
        Route::post('/{uuid}/reply', 'reply')->name('complaints.reply');
    });

/**
 * Category Routes
 * */
Route::controller(CategoryController::class)
    ->prefix('categories')
    ->group(function () {
        Route::get('/', 'categoryOnlyParent')->name('category.categoryOnlyParent');
        Route::get('/{id}/children', 'categoryWithChildren')->name('category.categoryWithChildren');
        Route::get('/{id}/attributes', 'categoryWithAttribute')->name('category.categoryWithAttribute');
    });

/**
 * User Routes
 * */
Route::controller(UserController::class)
    ->prefix('users')
    ->middleware('auth:sanctum')
    ->group(function () {
        // Favorites
        Route::get('/favorites', 'favorites')->name('users.favorites');
        Route::post('/favorites', 'favoriteAction')->name('users.favoriteAction');
        // Listings
        Route::get('/listings', 'listings')->name('users.listings');
        Route::get('/listings/total', 'listingsTotal')->name('users.listingsTotal');
        // Balance
        Route::get('/balance', 'balance')->name('users.balance');
        Route::get('/referrals', 'referrals')->name('users.referrals');
        Route::get('/payment-histories', 'paymentHistories')->name('users.paymentHistories');
        // Company Gallery
        Route::get('/company-galleries', 'companyGallery')->name('users.companyGallery');
        Route::post('/company-gallery/delete', 'companyGalleryDelete')->name('users.companyGalleryDelete');
        Route::post('/company-gallery/upload', 'companyGalleryUpload')->name('users.companyGalleryUpload');
    });

/**
 * Comment Routes
 * */
Route::controller(CommentController::class)
    ->prefix('comments')
    ->middleware(['auth:sanctum', 'check.blocked'])
    ->group(function () {
        Route::post('/save', 'save')->name('comments.save');
        Route::delete('/{id}/delete', 'delete')->name('comments.delete');
    });

/**
 * Messaging Routes
 * */
Route::controller(MessagingController::class)
    ->prefix('messaging')
    ->middleware(['auth:sanctum', 'check.blocked'])
    ->group(function () {
        Route::get('/conversations', 'getConversations');
        Route::get('/conversations/{uuid}', 'getConversation');
        Route::get('/conversations/{uuid}/messages', 'getMessages');
        Route::post('/send', 'sendMessage');
        Route::post('/conversations', 'createConversation');
        Route::post('/conversations/{uuid}/pin', 'togglePin');
        Route::post('/conversations/{uuid}/archive', 'archiveConversation');
        Route::post('/conversations/{uuid}/unArchive', 'unArchiveConversation');
        Route::post('/mark-as-read', 'markMessagesAsRead');
        Route::get('/search-users', 'searchUsers');
        Route::get('/unread-count', 'getUnreadCount');

        Route::post('/block-user', [UserBlockController::class, 'blockUser']);
        Route::post('/unblock-user', [UserBlockController::class, 'unblockUser']);
        Route::get('/blocked-users', [UserBlockController::class, 'getBlockedUsers']);
    });


