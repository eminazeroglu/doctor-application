<?php

use App\Enums\NotificationPriorityEnum;
use App\Enums\NotificationStatusEnum;
use App\Http\Controllers\Api\Admin\MailLogController;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('mail-tracking')->group(function () {
    Route::get('/open/{uuid}', [MailLogController::class, 'trackOpen'])
        ->name('mail-tracking.open');

    Route::get('/click/{uuid}/{url}', [MailLogController::class, 'trackClick'])
        ->name('mail-tracking.click');
});

Route::get('/', function () {
    $user = User::first();

    if (!$user) {
        return "First create a user!";
    }

    // Create notification
    $notification = new Notification();
    $notification->notifiable_type = User::class;
    $notification->notifiable_id = $user->id;
    $notification->type = 'welcome_message';
    $notification->data = [
        'title' => 'Xoş gəldiniz!',
        'message' => 'Saytımıza xoş gəldiniz, ' . $user->name,
        'action_url' => '/dashboard',
        'action_text' => 'Dashboard-a keçid'
    ];
    $notification->priority = NotificationPriorityEnum::NORMAL;
    $notification->status = NotificationStatusEnum::PENDING;
    $notification->save();

    return "Test notification created for user: " . $user->name;
});

