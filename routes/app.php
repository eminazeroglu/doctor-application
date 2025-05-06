<?php

use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\ReferralController;
use App\Http\Controllers\Api\Admin\SeoLinkToolsController;
use App\Http\Controllers\Api\Admin\StoryController;
use App\Http\Controllers\Api\Front\CategoryController;
use App\Http\Controllers\Api\Front\CommentController;
use App\Http\Controllers\Api\Front\CompanyController;
use App\Http\Controllers\Api\Front\ComplaintsController;
use App\Http\Controllers\Api\Front\ListingController;
use App\Http\Controllers\Api\Front\MessagingController;
use App\Http\Controllers\Api\Front\PageController;
use App\Http\Controllers\Api\Front\SectionController;
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
 *  Story Routes
 * */
Route::controller(StoryController::class)
    ->prefix('stories')
    ->group(function () {
        // Feed - bütün aktiv stories
        Route::get('feed', 'feed');

        // İstifadəçinin storyləri
        Route::get('user/{userId}', 'userStories');

        // Story baxış əlavə etmə
        Route::post('{uuid}/view', 'addView');

        // Story statistikası
        Route::get('{uuid}/stats', 'getStats');
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
 * Referral Routes
 * */
Route::controller(ReferralController::class)
    ->prefix('referrals')
    ->group(function () {
        Route::middleware(['auth:sanctum']) // Yalnız giriş etmiş istifadəçilər
        ->group(function () {
            // Statistika və məlumatlar
            Route::get('/stats', 'userStats')
                ->name('user.referrals.stats');  // İstifadəçinin referral statistikası

            Route::get('/list', 'userReferrals')
                ->name('user.referrals.list');  // Dəvət edilən istifadəçilərin siyahısı

            // Kod idarəetməsi
            Route::post('/generate-code', 'userGenerateCode')
                ->name('user.referrals.generateCode');  // Yeni referral kodu yaratmaq

            // Maliyyə əməliyyatları
            Route::get('/earnings', 'userEarnings')
                ->name('user.referrals.earnings');  // Qazanc tarixçəsi

            Route::get('/rewards', 'userRewards')
                ->name('user.referrals.rewards');  // Aktiv kampaniyalar
        });

        // Qeydiyyat zamanı referral kodunu yoxlamaq üçün public route
        Route::get('/check-code/{code}', 'userValidateCode')
            ->name('referrals.checkCode');  // Kodun etibarlılığını yoxlayır
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
 * Company Routes
 * */
Route::controller(CompanyController::class)
    ->prefix('companies')
    ->group(function () {
        Route::get('/', 'index')->name('company.index');
        Route::post('/create', 'create')->name('company.create');
        Route::get('/packages', 'packages')->name('company.packages');
        Route::get('/benefits', 'benefits')->name('company.benefits');
        Route::get('/{slug}', 'show')->name('company.show');
        Route::get('/{company:slug}/comments', 'comments')->name('company.comments');
        Route::get('/{company:slug}/listings', 'listings')->name('company.listings');
    });

/*
 * Section Routes
 * */
Route::controller(SectionController::class)
    ->prefix('sections')
    ->group(function () {
        Route::get('/', 'sectionList')->name('section.sectionList')->middleware('auth.optional');
    });

/**
 * Listing Routes
 * */
Route::controller(ListingController::class)
    ->prefix('listings')
    ->group(function () {
        Route::post('/search', 'search')->name('listings.search');
        Route::post('/by-uuids', 'listByUuids')->name('listings.listByUuids');
        Route::get('/{listing}/all', 'showAll')->name('listings.showAll');
        Route::get('/{listing:slug}/comments', 'comments')->name('listings.comments');
        Route::get('/{listing:slug}/related', 'related')->name('listings.related');
        Route::post('/{listing:slug}/complaint', 'complaint')->name('listings.complaint')->middleware('auth:sanctum');
        Route::get('/payment-services', 'paymentServices')->name('listings.paymentServices');
        Route::post('/create', 'create')->name('listings.create')->middleware('auth:sanctum');
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


