<?php

use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\SeoLinkToolsController;
use App\Http\Controllers\Api\Front\AppointmentController;
use App\Http\Controllers\Api\Front\BlogController;
use App\Http\Controllers\Api\Front\CategoryController;
use App\Http\Controllers\Api\Front\ComplaintsController;
use App\Http\Controllers\Api\Front\DoctorAppointmentController;
use App\Http\Controllers\Api\Front\DoctorCalendarController;
use App\Http\Controllers\Api\Front\DoctorController;
use App\Http\Controllers\Api\Front\DoctorDashboardController;
use App\Http\Controllers\Api\Front\FaqController;
use App\Http\Controllers\Api\Front\MessagingController;
use App\Http\Controllers\Api\Front\PageController;
use App\Http\Controllers\Api\Front\ProfileController;
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

/**
 * Appointment Routes
 */
Route::controller(AppointmentController::class)
    ->prefix('appointments')
    ->middleware(['auth:sanctum'])
    ->group(function () {

        // Randevu siyahısı
        Route::get('/', 'index')->name('appointments.index');

        // Filter seçimləri
        Route::get('/filters', 'getFilters')->name('appointments.filters');

        // Statistikalar
        Route::get('/stats', 'stats')->name('appointments.stats');

        // Excel export
        Route::get('/export', 'export')->name('appointments.export');

        // Randevu detalları
        Route::get('/{uuid}', 'show')->name('appointments.show');

        // Randevu ləğvi
        Route::delete('/{uuid}', 'cancel')->name('appointments.cancel');

        // Rəy yazma
        Route::post('/{uuid}/review', 'createReview')->name('appointments.review');
    });


/**
 * Profile Routes - app.php faylına əlavə olunacaq hissə
 */
Route::controller(ProfileController::class)
    ->prefix('profile')
    ->middleware(['auth:sanctum'])
    ->group(function () {

        // Profil məlumatlarını əldə etmək
        Route::get('/', 'index')->name('profile.index');

        // Profil məlumatlarını yeniləmək (ümumi - ad, soyad, telefon, ünvan)
        Route::put('/', 'update')->name('profile.update');

        // Hesab tənzimləmələri (email və şifrə yeniləməsi)
        Route::put('/account', 'updateAccount')->name('profile.updateAccount');

        // Profil şəklini yeniləmək
        Route::post('/avatar', 'updateAvatar')->name('profile.updateAvatar');

        // User preferences
        Route::get('/preferences', 'getPreferences')->name('profile.getPreferences');
        Route::put('/preferences', 'updatePreferences')->name('profile.updatePreferences');

        // Hesab idarəetməsi
        Route::post('/deactivate', 'deactivate')->name('profile.deactivate');
        Route::delete('/', 'destroy')->name('profile.destroy');

        /*
        |--------------------------------------------------------------------------
        | DOCTOR PROFILE ROUTES - Həkim Profil Route-ları
        |--------------------------------------------------------------------------
        */

        // Doctor Skills - Həkim Bacarıqları
        Route::prefix('doctor/skills')->group(function () {
            Route::get('/', 'getDoctorSkills')->name('profile.doctor.skill.index');
            Route::put('/', 'updateDoctorSkills')->name('profile.doctor.skill.sync'); // Bulk update
        });

        // Doctor Education - Həkim Təhsil
        Route::prefix('doctor/educations')->group(function () {
            Route::get('/', 'getDoctorEducations')->name('profile.doctor.educations.index');
            Route::put('/', 'updateDoctorEducations')->name('profile.doctor.educations.sync'); // Bulk update
        });

        // Doctor Experience - Həkim İş Təcrübəsi
        Route::prefix('doctor/experiences')->group(function () {
            Route::get('/', 'getDoctorExperiences')->name('profile.doctor.experiences.index');
            Route::put('/', 'updateDoctorExperiences')->name('profile.doctor.experiences.sync'); // Bulk update
        });

        // Doctor Certificates - Həkim Sertifikatlar
        Route::prefix('doctor/certificates')->group(function () {
            Route::get('/', 'getDoctorCertificates')->name('profile.doctor.certificates.index');
            Route::post('/', 'storeDoctorCertificate')->name('profile.doctor.certificates.store');
            Route::delete('/{id}', 'deleteDoctorCertificate')->name('profile.doctor.certificates.delete');
        });
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

Route::controller(DoctorDashboardController::class)
    ->prefix('doctor/dashboard')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/summary', 'summary');
        Route::get('/performance', 'performance');
        Route::get('/appointments/report', 'appointmentReport');
        Route::get('/bookings/recent', 'recentBookings');
        Route::get('/appointments/report/export', 'exportAppointmentReport');
    });

Route::controller(DoctorCalendarController::class)
    ->prefix('doctor/calendar')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        // Kalendar feed (appointments + schedules + unavailability)
        Route::get('/', 'index');

        // Recurring availability
        Route::post('/availability/recurring', 'storeRecurring');
        Route::put('/availability/recurring/{id}', 'updateRecurring');
        Route::delete('/availability/recurring/{id}', 'destroyRecurring');

        // Unavailability (busy)
        Route::post('/unavailability', 'storeUnavailability');
        Route::delete('/unavailability/{id}', 'destroyUnavailability');

        // Appointment status management (agenda popup)
        Route::put('/appointments/{id}/status', 'updateAppointmentStatus');
    });

Route::controller(DoctorAppointmentController::class)
    ->middleware('auth:sanctum')
    ->prefix('doctor/appointments')
    ->group(function () {
        Route::get('/', 'index')->name('appointments.doctorIndex');
        Route::get('/report', 'report')->name('appointments.doctorReport');
        Route::get('/{id}', 'show')->name('appointments.doctorShow');
        Route::put('/{id}/status', 'updateStatus')->name('appointments.doctorStatus');
        Route::put('/{id}/reschedule', 'reschedule')->name('appointments.doctorReschedule');
        Route::put('/{id}/note', 'updateNote')->name('appointments.doctorNote');
        Route::delete('/{id}', 'cancel')->name('appointments.doctorCancel');
    });
