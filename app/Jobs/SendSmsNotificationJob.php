<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\Notification\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Notification $notification;
    public User $user;
    public string $content;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(Notification $notification, User $user, string $content)
    {
        $this->notification = $notification;
        $this->user = $user;
        $this->content = $content;
    }

    public function handle(SmsService $smsService): void
    {
        try {
            $result = $smsService->send($this->user->phone, $this->content);

            $this->logNotification($result['success'], $result['error'] ?? null);

        } catch (\Exception $e) {
            $this->logNotification(false, $e->getMessage());

            Log::error('SMS notification failed', [
                'notification_id' => $this->notification->id,
                'user_id' => $this->user->id,
                'phone' => $this->user->phone,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SMS notification job failed permanently', [
            'notification_id' => $this->notification->id,
            'user_id' => $this->user->id,
            'phone' => $this->user->phone,
            'error' => $exception->getMessage()
        ]);
    }

    private function logNotification(bool $isSuccessful, ?string $errorMessage = null): void
    {
        NotificationLog::create([
            'user_id' => $this->user->id,
            'channel' => 'sms',
            'recipient' => $this->user->phone,
            'notification_type' => $this->notification->type,
            'template_code' => null,
            'subject' => null,
            'content' => $this->content,
            'is_successful' => $isSuccessful,
            'error_message' => $errorMessage,
            'sent_at' => now(),
        ]);
    }
}
