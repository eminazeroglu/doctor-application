<?php

namespace App\Listeners\Notification;

use App\Events\Notification\NotificationRead;
use App\Services\Module\NotificationService;
use Illuminate\Support\Facades\Cache;

/**
 * Notification oxunduqda cache yeniləyən listener
 */
class UpdateNotificationCache
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(NotificationRead $event): void
    {
        // Cache key-lərini yeniləmək
        $cacheKey = "user_notifications_unread_{$event->user->id}";
        Cache::put($cacheKey, $event->unreadCount, now()->addMinutes(60));

        // İstifadəçinin notification stats cache-ni təmizləmək
        $statsCacheKey = "user_notification_stats_{$event->user->id}";
        Cache::forget($statsCacheKey);
    }
}
