<?php

namespace App\Services\Module;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Exceptions\BaseException;
use App\Models\Notification;
use App\Models\NotificationDevice;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Repositories\Module\NotificationRepository;
use App\Services\BaseCrudService;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService extends BaseCrudService
{
    public function __construct(NotificationRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * İstifadəçiyə notification göndərmək
     */
    public function send(
        Model $notifiable,
        string $type,
        array $data = [],
        ?string $priority = null,
        ?Carbon $sendAt = null
    ): Notification {
        try {
            DB::beginTransaction();

            // Notification yaradırıq
            $notification = $this->create([
                'type' => $type,
                'user_id' => $notifiable->id,
                'title' => $data['title'] ?? NotificationTypeEnum::getDescription($type),
                'content' => $data['content'] ?? '',
                'icon' => $data['icon'] ?? null,
                'action_url' => $data['action_url'] ?? null,
                'action_text' => $data['action_text'] ?? null,
                'data' => $data,
                'send_at' => $sendAt,
                'is_sent' => $sendAt === null
            ]);

            // Əgər dərhal göndəriləcəksə, kanalları müəyyən edib göndəririk
            if ($sendAt === null) {
                $this->sendToChannels($notifiable, $notification);
            }

            DB::commit();
            return $notification;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Notification send failed', [
                'notifiable_id' => $notifiable->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Planlaşdırılmış notification yaratmaq
     */
    public function schedule(
        Model $notifiable,
        string $type,
        Carbon $sendAt,
        array $data = []
    ): Notification {
        return $this->send($notifiable, $type, $data, null, $sendAt);
    }

    /**
     * Müxtəlif kanallara notification göndərmək
     */
    public function sendToChannels(Model $notifiable, Notification $notification): void
    {
        $preferences = $this->getUserNotificationPreferences($notifiable->id, $notification->type);

        // E-poçt göndərmək
        if ($preferences['email_enabled'] ?? true) {
            $this->sendEmail($notifiable, $notification);
        }

        // Push notification göndərmək
        if ($preferences['push_enabled'] ?? true) {
            $this->sendPushNotification($notifiable, $notification);
        }

        // SMS göndərmək (əgər telefon nömrəsi varsa)
        if (($preferences['sms_enabled'] ?? false) && $notifiable->phone) {
            $this->sendSms($notifiable, $notification);
        }

        // In-app notification avtomatik yaranır
    }

    /**
     * E-poçt göndərmək
     */
    protected function sendEmail(Model $notifiable, Notification $notification): void
    {
        try {
            $template = $this->getTemplate(NotificationChannelEnum::Email, $notification->type);

            if ($template && $notifiable->email) {
                $subject = $template->parseSubject($notification->data ?? []) ?? $notification->title;
                $content = $template->parseContent($notification->data ?? []);

                // Mail göndərmə (Real implementation lazımdır)
                // Mail::to($notifiable->email)->send(new NotificationMail($subject, $content));

                $this->logNotification(
                    $notifiable->id,
                    NotificationChannelEnum::Email,
                    $notifiable->email,
                    $notification->type,
                    $template->code ?? null,
                    $subject,
                    $content,
                    true
                );
            }
        } catch (Exception $e) {
            $this->logNotification(
                $notifiable->id,
                NotificationChannelEnum::Email,
                $notifiable->email,
                $notification->type,
                null,
                $notification->title,
                $notification->content,
                false,
                $e->getMessage()
            );
        }
    }

    /**
     * Push notification göndərmək
     */
    protected function sendPushNotification(Model $notifiable, Notification $notification): void
    {
        try {
            $devices = NotificationDevice::where('user_id', $notifiable->id)
                ->where('is_active', true)
                ->get();

            foreach ($devices as $device) {
                // Push notification göndərmə logikası (Firebase, APNs və s.)
                $this->sendPushToDevice($device, $notification);

                $this->logNotification(
                    $notifiable->id,
                    NotificationChannelEnum::Push,
                    $device->device_token,
                    $notification->type,
                    null,
                    $notification->title,
                    $notification->content,
                    true
                );
            }
        } catch (Exception $e) {
            $this->logNotification(
                $notifiable->id,
                NotificationChannelEnum::Push,
                'push_tokens',
                $notification->type,
                null,
                $notification->title,
                $notification->content,
                false,
                $e->getMessage()
            );
        }
    }

    /**
     * SMS göndərmək
     */
    protected function sendSms(Model $notifiable, Notification $notification): void
    {
        try {
            $template = $this->getTemplate(NotificationChannelEnum::Sms, $notification->type);
            $content = $template ?
                $template->parseContent($notification->data ?? []) :
                $notification->content;

            // SMS göndərmə logikası
            // SmsService::send($notifiable->phone, $content);

            $this->logNotification(
                $notifiable->id,
                NotificationChannelEnum::Sms,
                $notifiable->phone,
                $notification->type,
                $template->code ?? null,
                null,
                $content,
                true
            );
        } catch (Exception $e) {
            $this->logNotification(
                $notifiable->id,
                NotificationChannelEnum::Sms,
                $notifiable->phone,
                $notification->type,
                null,
                null,
                $notification->content,
                false,
                $e->getMessage()
            );
        }
    }

    /**
     * Push notification göndərmək üçün köməkçi metod
     */
    protected function sendPushToDevice(NotificationDevice $device, Notification $notification): void
    {
        // Firebase, APNs və ya digər push service ilə əlaqə
        // Bu hissə konfiqurasiyaya görə dəyişəcək
    }

    /**
     * Şablon tapmaq
     */
    protected function getTemplate(string $channel, string $type): ?NotificationTemplate
    {
        return NotificationTemplate::where('channel', $channel)
            ->where('type', $type)
            ->where('is_active', true)
            ->first();
    }

    /**
     * İstifadəçi tənzimləmələri əldə etmək
     */
    protected function getUserNotificationPreferences(int $userId, string $type): array
    {
        $preference = NotificationPreference::where('user_id', $userId)
            ->where('notification_type', $type)
            ->first();

        return $preference ? [
            'email_enabled' => $preference->email_enabled,
            'sms_enabled' => $preference->sms_enabled,
            'push_enabled' => $preference->push_enabled,
            'in_app_enabled' => $preference->in_app_enabled,
        ] : [
            'email_enabled' => true,
            'sms_enabled' => false,
            'push_enabled' => true,
            'in_app_enabled' => true,
        ];
    }

    /**
     * Notification log yazmaq
     */
    protected function logNotification(
        ?int $userId,
        string $channel,
        string $recipient,
        string $notificationType,
        ?string $templateCode,
        ?string $subject,
        string $content,
        bool $isSuccessful,
        ?string $errorMessage = null
    ): void {
        NotificationLog::create([
            'user_id' => $userId,
            'channel' => $channel,
            'recipient' => $recipient,
            'notification_type' => $notificationType,
            'template_code' => $templateCode,
            'subject' => $subject,
            'content' => $content,
            'is_successful' => $isSuccessful,
            'error_message' => $errorMessage,
            'sent_at' => now(),
        ]);
    }

    /**
     * İstifadəçinin notification-larını əldə etmək
     */
    public function getUserNotifications(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::where('user_id', $userId);

        // Filter tətbiq etmək
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['read'])) {
            if ($filters['read']) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate(request()->get('limit', 20));
    }

    /**
     * Notification-ı oxunmuş kimi qeyd etmək
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            throw new BaseException(['message' => 'Notification tapılmadı'], 404);
        }

        return $notification->markAsRead();
    }

    /**
     * Bütün notification-ları oxunmuş kimi qeyd etmək
     */
    public function markAllAsRead(Model $notifiable): int
    {
        return Notification::where('user_id', $notifiable->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Oxunmamış notification sayı
     */
    public function getUnreadCount(Model $notifiable): int
    {
        return Notification::where('user_id', $notifiable->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * İstifadəçi statistikaları
     */
    public function getStats(Model $notifiable): array
    {
        $userId = $notifiable->id;

        return [
            'total_notifications' => Notification::where('user_id', $userId)->count(),
            'unread_notifications' => $this->getUnreadCount($notifiable),
            'notifications_by_type' => Notification::where('user_id', $userId)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'recent_notifications' => Notification::where('user_id', $userId)
                ->latest()
                ->limit(5)
                ->get(['id', 'title', 'type', 'read_at', 'created_at']),
        ];
    }

    /**
     * Kütləvi notification göndərmək
     * @throws Exception
     */
    public function sendBulk(array $userIds, string $type, array $data = []): array
    {
        $results = [];

        try {
            DB::beginTransaction();

            foreach ($userIds as $userId) {
                $user = User::find($userId);
                if ($user) {
                    try {
                        $notification = $this->send($user, $type, $data);
                        $results['success'][] = [
                            'user_id' => $userId,
                            'notification_id' => $notification->id
                        ];
                    } catch (Exception $e) {
                        $results['failed'][] = [
                            'user_id' => $userId,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'total' => count($userIds),
            'successful' => count($results['success'] ?? []),
            'failed' => count($results['failed'] ?? []),
            'details' => $results
        ];
    }

    /**
     * Planlaşdırılmış notification-ları göndərmək
     */
    public function sendScheduledNotifications(): int
    {
        $notifications = Notification::where('is_sent', false)
            ->where(function($q) {
                $q->whereNull('send_at')
                    ->orWhere('send_at', '<=', now());
            })
            ->get();

        $count = 0;
        foreach ($notifications as $notification) {
            try {
                $user = User::find($notification->user_id);
                if ($user) {
                    $this->sendToChannels($user, $notification);
                    $notification->markAsSent();
                    $count++;
                }
            } catch (Exception $e) {
                Log::error('Scheduled notification failed', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }

    /**
     * Cihaz qeydiyyatı
     */
    public function registerDevice(int $userId, array $deviceData): NotificationDevice
    {
        return NotificationDevice::updateOrCreate(
            [
                'user_id' => $userId,
                'device_token' => $deviceData['device_token']
            ],
            [
                'device_type' => $deviceData['device_type'],
                'device_name' => $deviceData['device_name'] ?? null,
                'app_version' => $deviceData['app_version'] ?? null,
                'is_active' => true,
                'last_used_at' => now()
            ]
        );
    }

    /**
     * Admin üçün statistika
     */
    public function getAdminStats(): array
    {
        return [
            'total_notifications' => Notification::count(),
            'sent_today' => Notification::whereDate('created_at', today())->count(),
            'pending_notifications' => Notification::where('is_sent', false)->count(),
            'by_channel' => NotificationLog::selectRaw('channel, COUNT(*) as count')
                ->groupBy('channel')
                ->pluck('count', 'channel')
                ->toArray(),
            'success_rate' => $this->calculateSuccessRate(),
            'most_active_users' => $this->getMostActiveUsers(),
        ];
    }

    /**
     * Uğur dərəcəsi hesablamaq
     */
    protected function calculateSuccessRate(): array
    {
        $total = NotificationLog::count();
        $successful = NotificationLog::where('is_successful', true)->count();

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $total - $successful,
            'rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0
        ];
    }

    /**
     * Ən aktiv istifadəçiləri tapmaq
     */
    protected function getMostActiveUsers(): Collection
    {
        return User::withCount('notifications')
            ->orderByDesc('notifications_count')
            ->limit(10)
            ->get(['id', 'name', 'surname', 'email']);
    }
}
