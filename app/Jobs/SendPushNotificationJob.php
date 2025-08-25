<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\NotificationDevice;
use App\Models\NotificationLog;
use App\Services\Notification\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Notification $notification;
    public NotificationDevice $device;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(Notification $notification, NotificationDevice $device)
    {
        $this->notification = $notification;
        $this->device = $device;
    }

    public function handle(PushNotificationService $pushService): void
    {
        try {
            $result = $pushService->sendToDevice(
                $this->device,
                $this->notification->title,
                $this->notification->content,
                [
                    'notification_id' => $this->notification->id,
                    'type' => $this->notification->type,
                    'action_url' => $this->notification->action_url,
                    'data' => $this->notification->data
                ]
            );

            $this->logNotification($result['success'], $result['error'] ?? null);

            if ($result['success']) {
                $this->device->markAsUsed();
            }

        } catch (\Exception $e) {
            $this->logNotification(false, $e->getMessage());

            Log::error('Push notification failed', [
                'notification_id' => $this->notification->id,
                'device_id' => $this->device->id,
                'device_token' => $this->device->device_token,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Push notification job failed permanently', [
            'notification_id' => $this->notification->id,
            'device_id' => $this->device->id,
            'error' => $exception->getMessage()
        ]);

        // Çox uğursuzluq olarsa cihazı deaktiv et
        if ($this->attempts() >= $this->tries) {
            $this->device->deactivate();
        }
    }

    private function logNotification(bool $isSuccessful, ?string $errorMessage = null): void
    {
        NotificationLog::create([
            'user_id' => $this->device->user_id,
            'channel' => 'push',
            'recipient' => $this->device->device_token,
            'notification_type' => $this->notification->type,
            'template_code' => null,
            'subject' => $this->notification->title,
            'content' => $this->notification->content,
            'is_successful' => $isSuccessful,
            'error_message' => $errorMessage,
            'sent_at' => now(),
        ]);
    }
}
