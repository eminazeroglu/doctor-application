<?php

use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\SeoLinkToolsController;
use App\Http\Controllers\Api\Front\BlogController;
use App\Http\Controllers\Api\Front\CategoryController;
use App\Http\Controllers\Api\Front\CommentController;
use App\Http\Controllers\Api\Front\ComplaintsController;
use App\Http\Controllers\Api\Front\DoctorController;
use App\Http\Controllers\Api\Front\FaqController;
use App\Http\Controllers\Api\Front\MessagingController;
use App\Http\Controllers\Api\Front\PageController;
use App\Http\Controllers\Api\Front\UserBlockController;
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
        // İstifadəçi notification-ları
        Route::get('/user', 'userNotifications')->name('notifications.user');
        Route::get('/user/latest', 'getLatest')->name('notifications.latest');
        Route::get('/user/stats', 'userStats')->name('notifications.userStats');

        // Notification oxuma əməliyyatları
        Route::post('/{id}/read', 'markAsRead')->name('notifications.markAsRead');
        Route::post('/read-all', 'markAllAsRead')->name('notifications.markAllAsRead');
        Route::delete('/{id}', 'deleteNotification')->name('notifications.delete');

        // Cihaz idarəetməsi
        Route::post('/devices/register', 'registerDevice')->name('notifications.registerDevice');
        Route::post('/devices/unregister', 'unregisterDevice')->name('notifications.unregisterDevice');
        Route::get('/devices', 'getUserDevices')->name('notifications.getUserDevices');

        // Tənzimləmələr
        Route::get('/preferences', 'getPreferences')->name('notifications.getPreferences');
        Route::put('/preferences', 'updatePreferences')->name('notifications.updatePreferences');
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
        Route::get('/{id}/services', 'categoryWithServices')->name('category.categoryWithServices');
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

/**
 * Doctor Routes
 * */
Route::controller(DoctorController::class)
    ->prefix('doctors')
    ->group(function () {
        Route::post('/search', 'doctorSearch')->name('doctor.search');
        Route::get('/{id}/view', 'doctorView')->name('doctor.view');
    });

/**
 * Blog Routes
 * */
Route::controller(BlogController::class)
    ->prefix('blogs')
    ->group(function () {
        Route::get('/', 'blogSearch')->name('blog.search');
        Route::get('/{slug}', 'blogView')->name('blog.view');
    });

/**
 * Faq Routes
 * */
Route::controller(FaqController::class)
    ->prefix('faqs')
    ->group(function () {
        Route::get('/', 'index')->name('faq.index');
    });
