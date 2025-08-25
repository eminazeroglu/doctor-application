<?php

namespace App\Jobs;

use App\Mail\NotificationMail;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Notification $notification;
    public User $user;
    public string $subject;
    public string $content;

    /**
     * Job retry count
     */
    public int $tries = 3;

    /**
     * Job timeout in seconds
     */
    public int $timeout = 60;

    public function __construct(Notification $notification, User $user, string $subject, string $content)
    {
        $this->notification = $notification;
        $this->user = $user;
        $this->subject = $subject;
        $this->content = $content;
    }

    public function handle(): void
    {
        try {
            Mail::to($this->user->email)->send(
                new NotificationMail($this->notification, $this->user, $this->subject, $this->content)
            );

            // Uğurlu göndərmə loqu
            $this->logNotification(true);

        } catch (\Exception $e) {
            // Xəta loqu
            $this->logNotification(false, $e->getMessage());

            Log::error('Email notification failed', [
                'notification_id' => $this->notification->id,
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Email notification job failed permanently', [
            'notification_id' => $this->notification->id,
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'error' => $exception->getMessage()
        ]);
    }

    private function logNotification(bool $isSuccessful, ?string $errorMessage = null): void
    {
        NotificationLog::create([
            'user_id' => $this->user->id,
            'channel' => 'email',
            'recipient' => $this->user->email,
            'notification_type' => $this->notification->type,
            'template_code' => null,
            'subject' => $this->subject,
            'content' => $this->content,
            'is_successful' => $isSuccessful,
            'error_message' => $errorMessage,
            'sent_at' => now(),
        ]);
    }
}
