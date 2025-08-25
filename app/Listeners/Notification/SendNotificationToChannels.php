<?php

namespace App\Listeners\Notification;

use App\Events\Notification\NotificationCreated;
use App\Services\Module\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notification yaradıldıqda digər kanallara göndərən listener
 */
class SendNotificationToChannels implements ShouldQueue
{
    use InteractsWithQueue;

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(NotificationCreated $event): void
    {
        try {
            // In-app notification avtomatik yaranır
            // Digər kanallara (email, push, sms) göndərmək
            $this->notificationService->sendToChannels($event->user, $event->notification);

        } catch (\Exception $e) {
            Log::error('Failed to send notification to channels', [
                'notification_id' => $event->notification->id,
                'user_id' => $event->user->id,
                'error' => $e->getMessage()
            ]);

            // Retry mechanism
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(NotificationCreated $event, \Throwable $exception): void
    {
        Log::error('Notification sending failed permanently', [
            'notification_id' => $event->notification->id,
            'user_id' => $event->user->id,
            'error' => $exception->getMessage()
        ]);
    }
}
