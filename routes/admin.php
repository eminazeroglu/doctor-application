<?php

use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\AdvertisementController;
use App\Http\Controllers\Api\Admin\AppointmentController;
use App\Http\Controllers\Api\Admin\AttributeController;
use App\Http\Controllers\Api\Admin\BlockedCredentialController;
use App\Http\Controllers\Api\Admin\BlogController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\CityController;
use App\Http\Controllers\Api\Admin\ClinicController;
use App\Http\Controllers\Api\Admin\CommentController;
use App\Http\Controllers\Api\Admin\ComplaintsController;
use App\Http\Controllers\Api\Admin\CountryController;
use App\Http\Controllers\Api\Admin\DoctorController;
use App\Http\Controllers\Api\Admin\FaqController;
use App\Http\Controllers\Api\Admin\LanguageController;
use App\Http\Controllers\Api\Admin\MessagingController;
use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\PageController;
use App\Http\Controllers\Api\Admin\PatientController;
use App\Http\Controllers\Api\Admin\PaymentController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\RegionController;
use App\Http\Controllers\Api\Admin\ReviewController;
use App\Http\Controllers\Api\Admin\SeoLinkController;
use App\Http\Controllers\Api\Admin\ServiceController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\SliderController;
use App\Http\Controllers\Api\Admin\SubwayController;
use App\Http\Controllers\Api\Admin\TranslationController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

/**
 * Languages Routes
 * */
Route::resource('languages', LanguageController::class);

/**
 * Translation Routes
 * */
Route::controller(TranslationController::class)->prefix('translations')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/filters', 'filters')->name('filters');
    Route::post('/save', 'save')->name('save');
    Route::delete('/{id}', 'destroy')->name('save');
});

/**
 * User Routes
 * */
Route::controller(UserController::class)->prefix('users')->group(function () {
    Route::get('/dashboard', 'dashboard')->name('users.dashboard');
});
Route::resource('users', UserController::class);

/**
 * Page & Page Widgets Routes
 * */
Route::resource('pages', PageController::class);
Route::controller(PageController::class)->prefix('pages')->group(function () {
    Route::post('/widgets', 'createWidget');
    Route::put('/widgets/{id}', 'updateWidget');
    Route::delete('/widgets/{id}', 'deleteWidget');
    Route::post('/widgets/{id}/status', 'changeWidgetStatus');
    Route::get('/widgets/{id}', 'getWidget');
    Route::get('/{pageId}/widgets', 'getWidgets');
    Route::post('/widgets/reorder', 'reorderWidgets');
});

/**
 * Settings Routes
 * */
Route::prefix('settings')->as('settings.')->group(function () {
    Route::get('/', [SettingController::class, 'index'])->name('index');
    Route::get('/{key}', [SettingController::class, 'show'])->name('show');
    Route::put('/{key}', [SettingController::class, 'update'])->name('update');
});

/**
 * Notification Routes
 * */
Route::controller(NotificationController::class)->prefix('notifications')->group(function () {
    Route::get('/', 'index')->name('notifications.index');
    Route::post('/', 'store')->name('notifications.store');
    Route::get('/filters', 'filters')->name('notifications.filters');
    Route::delete('/{id}', 'destroy')->name('notifications.destroy');
    Route::post('/bulk-delete', 'bulkDelete')->name('notifications.bulkDelete');
});

/**
 * Advertisement Routes
 * */
Route::resource('advertisements', AdvertisementController::class);

/**
 * Permission Routes
 * */
Route::resource('permissions', PermissionController::class);
Route::controller(PermissionController::class)->prefix('permissions')->group(function () {
    Route::get('/{permission}/grouped', 'groupedPermissions')->name('permissions.groupedPermissions');
    Route::post('/{permission}/updatePermissions', 'updatePermissions')->name('permissions.updatePermissions');
});

/**
 * Seo Link Routes
 */
Route::prefix('seo-links')->group(function () {
    Route::get('analyze/{uuid}', [SeoLinkController::class, 'analyze']);
    Route::post('validate', [SeoLinkController::class, 'validate']);
    Route::post('suggestions', [SeoLinkController::class, 'suggestions']);
    Route::get('preview/{uuid}', [SeoLinkController::class, 'preview']);
    Route::post('add-tag/{uuid}', [SeoLinkController::class, 'addTag']);
    Route::delete('remove-tag/{uuid}', [SeoLinkController::class, 'removeTag']);
    Route::get('history/{uuid}', [SeoLinkController::class, 'history']);
    Route::post('type-options', [SeoLinkController::class, 'typeOptions']);
});
Route::resource('seo-links', SeoLinkController::class);

/**
 * Blocked Credential Routes
 */
Route::resource('blocked-credentials', BlockedCredentialController::class);

/**
 * Payment Routes
 */
Route::controller(PaymentController::class)->prefix('payments')->group(function () {
    Route::get('/', 'index')->name('payments.index');
    Route::get('/filters', 'filters')->name('payments.filters');
    Route::get('/{id}', 'show')->name('payments.show');
    Route::post('/{id}/complete', 'complete')->name('payments.complete');
    Route::post('/{id}/fail', 'fail')->name('payments.fail');
    Route::post('/{id}/refund', 'refund')->name('payments.refund');
    Route::post('/{id}/retry', 'retry')->name('payments.retry');
});

/**
 * Messages Routes
 */
Route::controller(MessagingController::class)->prefix('messaging')->group(function () {
    Route::get('/dashboard', 'dashboard')->name('messaging.dashboard');
    Route::get('/conversations', 'getConversations')->name('messaging.getConversations');
    Route::get('/conversations/{uuid}', 'getConversation')->name('messaging.getConversation');
    Route::get('/conversations/{uuid}/messages', 'getConversationMessages')->name('messaging.getConversationMessages');
    Route::get('/messages', 'getAllMessages')->name('messaging.getAllMessages');
    Route::post('/conversations/{uuid}/block', 'blockConversation')->name('messaging.blockConversation');
    Route::post('/conversations/{uuid}/unblock', 'unblockConversation')->name('messaging.unblockConversation');
    Route::post('/conversations/{uuid}/system-message', 'sendSystemMessage')->name('messaging.sendSystemMessage');
    Route::delete('/messages/{uuid}', 'deleteMessage')->name('messaging.deleteMessage');
    Route::post('/bulk-message', 'sendBulkMessage')->name('messaging.sendBulkMessage');
});

/**
 * ActivityLog Routes
 * */
Route::controller(ActivityLogController::class)->prefix('activity-logs')->group(function () {
    Route::get('/', 'index')->name('activity-logs.index');
    Route::get('/filters', 'filters')->name('activity-logs.filters');
    Route::delete('/cleanup', 'cleanup')->name('activity-logs.cleanup');
});

// Category
Route::controller(CategoryController::class)->prefix('categories')->group(function () {
    Route::get('{categoryId}/attributes', 'attributes');
    Route::post('{categoryId}/attributes', 'attachAttribute');
    Route::delete('{categoryId}/attributes/{attributeId}', 'detachAttribute');
    Route::post('{categoryId}/attributes/order', 'attributeOrder');
    Route::get('/{categoryId}/config', 'config');
    Route::post('/{categoryId}/config-save', 'configSave');
});
Route::resource('categories', CategoryController::class);

// Attribute
Route::controller(AttributeController::class)->prefix('attributes')->group(function () {
    Route::get('{id}/options', 'listOptions');
    Route::post('{id}/options', 'syncOptions');
    Route::delete('{id}/options/{optionId}', 'deleteOption');
    Route::post('{id}/options/order', 'orderOption');
    Route::post('{id}/options/{optionId}/status', 'statusOption');
});
Route::resource('attributes', AttributeController::class);


/**
 * Comment Routes
 * */
Route::resource('comments', CommentController::class);

/**
 * Country Routes
 * */
Route::resource('countries', CountryController::class);

/**
 * City Routes
 * */
Route::resource('cities', CityController::class);

/**
 * Region Routes
 * */
Route::resource('regions', RegionController::class);

/**
 * Subway Routes
 * */
Route::resource('subways', SubwayController::class);

/**
 * Complaints Routes
 * */
Route::controller(ComplaintsController::class)->prefix('complaints')->group(function () {
    // Şikayəti statuslarını dəyişmək
    Route::post('/{id}/status', 'status')->name('complaints.status');
    // Şikayətə cavab yazmaq üçün
    Route::post('/{id}/reply', 'reply')->name('complaints.reply');
    // Şikayət statistikalarını göstərmək üçün
    Route::get('/stats', 'stats')->name('complaints.stats');
});
Route::resource('complaints', ComplaintsController::class);

// Service
Route::resource('services', ServiceController::class);

// Appointment
Route::resource('appointments', AppointmentController::class);
Route::controller(AppointmentController::class)->prefix('appointments')->group(function () {
    Route::get('/today', 'today')->name('appointments.today');
    Route::get('/statistics', 'statistics')->name('appointments.statistics');
    Route::post('/{id}/action', 'action')->name('appointments.action');
});

// Clinic
Route::controller(ClinicController::class)->prefix('clinics')->group(function () {
    Route::get('/nearby', 'nearby')->name('clinics.nearby');
    Route::get('/popular', 'popular')->name('clinics.popular');
    Route::get('/{clinic}/statistics', 'statistics')->name('clinics.statistics');
    Route::get('/{clinic}/available-slots', 'availableSlots')->name('clinics.available-slots');
    Route::post('/{clinic}/toggle-verified', 'toggleVerified')->name('clinics.toggle-verified');
    Route::post('/{clinic}/toggle-featured', 'toggleFeatured')->name('clinics.toggle-featured');
});
Route::resource('clinics', ClinicController::class);

// Doctor
Route::resource('doctors', DoctorController::class);
Route::controller(DoctorController::class)->prefix('doctors')->group(function () {
    Route::post('{id}/toggle-verification', 'toggleVerification')->name('doctors.toggleVerification');
    Route::post('{id}/toggle-featured', 'toggleFeatured')->name('doctors.toggleFeatured');
    Route::get('category/{categoryId}', 'getByCategory')->name('doctors.getByCategory');
    Route::get('clinic/{clinicId}', 'getByClinic')->name('doctors.getByClinic');
    Route::post('{id}/unavailability', 'addUnavailability')->name('doctors.addUnavailability');
    Route::get('{id}/check-availability', 'checkAvailability')->name('doctors.checkAvailability');
});

// Patient
Route::resource('patients', PatientController::class);

// Review

// Review
Route::controller(ReviewController::class)->prefix('reviews')->group(function () {
    // Xüsusi route-lar
    Route::get('/by-doctor/{doctorId}', 'byDoctor')->name('reviews.by-doctor');
    Route::get('/by-clinic/{clinicId}', 'byClinic')->name('reviews.by-clinic');
    Route::get('/awaiting-moderation', 'awaitingModeration')->name('reviews.awaiting-moderation');
    Route::get('/most-helpful', 'mostHelpful')->name('reviews.most-helpful');
    Route::get('/reported', 'reported')->name('reviews.reported');
    Route::get('/statistics', 'statistics')->name('reviews.statistics');

    // Status əməliyyatları
    Route::post('/{id}/verify', 'verify')->name('reviews.verify');
    Route::post('/{id}/moderate', 'moderate')->name('reviews.moderate');
});

Route::resource('reviews', ReviewController::class);

// Notification
Route::resource('notifications', NotificationController::class);
Route::controller(NotificationController::class)->prefix('notifications')->group(function () {
    // Statistika və hesabatlar
    Route::get('/statistics/admin', 'statistics')->name('notifications.statistics');

    // Kütləvi əməliyyatlar
    Route::post('/bulk/delete', 'bulkDelete')->name('notifications.bulkDelete');
    Route::post('/bulk/send-to-all', 'sendToAll')->name('notifications.sendToAll');

    // Planlaşdırılmış notification-lar
    Route::post('/scheduled/send', 'sendScheduled')->name('notifications.sendScheduled');
});

// Payment
Route::resource('payments', PaymentController::class);

// Blog
Route::resource('blogs', BlogController::class);

// Faq
Route::resource('faqs', FaqController::class);


// Slider
Route::resource('sliders', SliderController::class);
