<?php

namespace App\Traits\Model;

use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use App\Models\NotificationDevice;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Services\Module\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasNotification
{
    /**
     * İstifadəçinin bütün notification-ları
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    /**
     * İstifadəçinin notification cihazları
     */
    public function notificationDevices(): HasMany
    {
        return $this->hasMany(NotificationDevice::class, 'user_id');
    }

    /**
     * İstifadəçinin notification tənzimləmələri
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class, 'user_id');
    }

    /**
     * İstifadəçinin notification logları
     */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'user_id');
    }

    /**
     * Sadəcə oxunmamış notification-ları əldə etmək üçün relationship
     */
    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * Yüksək prioritetli notification-ları əldə etmək üçün relationship
     */
    public function highPriorityNotifications(): HasMany
    {
        return $this->notifications()
            ->whereJsonContains('data->priority', 'high')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Planlaşdırılmış notification-ları əldə etmək üçün relationship
     */
    public function scheduledNotifications(): HasMany
    {
        return $this->notifications()
            ->whereNotNull('send_at')
            ->where('send_at', '>', now())
            ->orderBy('send_at', 'asc');
    }

    /**
     * Son notification-lar
     */
    public function recentNotifications(): HasMany
    {
        return $this->notifications()->latest()->limit(10);
    }

    /**
     * Model-ə notification göndərmək üçün əsas method
     */
    public function notify(
        string $type,
        array $data = [],
        ?string $priority = null
    ): Notification {
        // Notification növünün validliyi yoxlanılır
        if (!NotificationTypeEnum::hasValue($type)) {
            throw new \InvalidArgumentException("Invalid notification type: {$type}");
        }

        // NotificationService-dən istifadə edərək notification yaradırıq
        return app(NotificationService::class)->send(
            notifiable: $this,
            type: $type,
            data: $data,
            priority: $priority
        );
    }

    /**
     * Notification-ın hansı kanallarla göndərilə biləcəyini müəyyən edir
     */
    public function shouldReceiveNotificationVia(string $channel, Notification $notification): bool
    {
        // İstifadəçi tənzimləmələrini yoxlamaq
        $canReceive = $this->canReceiveNotification($notification->type, $channel);

        if (!$canReceive) {
            return false;
        }

        // Kanal mövcudluğunu yoxlamaq
        return match($channel) {
            'email' => !empty($this->email),
            'sms' => !empty($this->phone),
            'push' => $this->getActiveDevices()->isNotEmpty(),
            'in_app' => true,
            default => false
        };
    }

    /**
     * Model üçün gələcək tarixə notification planlaşdırmaq
     */
    public function scheduleNotification(
        string $type,
        Carbon $sendAt,
        array $data = []
    ): Notification {
        return app(NotificationService::class)->schedule(
            notifiable: $this,
            type: $type,
            sendAt: $sendAt,
            data: $data
        );
    }

    /**
     * Bütün notification-ları oxunmuş kimi qeyd etmək
     */
    public function markAllNotificationsAsRead(): void
    {
        app(NotificationService::class)->markAllAsRead($this);
    }

    /**
     * Oxunmamış notification sayını əldə etmək
     */
    public function getUnreadNotificationCount(): int
    {
        return app(NotificationService::class)->getUnreadCount($this);
    }

    /**
     * Notification statistikalarını əldə etmək
     */
    public function getNotificationStats(): array
    {
        return app(NotificationService::class)->getStats($this);
    }

    /**
     * Notification göndərmə statistikalarını əldə etmək (NotificationLog əsasında)
     */
    public function getNotificationDeliveryStats(): array
    {
        return $this->notificationLogs()
            ->selectRaw('
                channel,
                COUNT(*) as total,
                SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed
            ')
            ->groupBy('channel')
            ->get()
            ->mapWithKeys(function ($stat) {
                return [$stat->channel => [
                    'total' => $stat->total,
                    'successful' => $stat->successful,
                    'failed' => $stat->failed,
                    'success_rate' => $stat->total > 0 ? round(($stat->successful / $stat->total) * 100, 2) : 0
                ]];
            })
            ->toArray();
    }

    /**
     * Son notification-ları əldə etmək
     */
    public function getLatestNotifications(int $limit = 5): Collection
    {
        return $this->notifications()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Müəyyən tip notification-ları əldə etmək
     */
    public function getNotificationsByType(string $type): Collection
    {
        if (!NotificationTypeEnum::hasValue($type)) {
            throw new \InvalidArgumentException("Invalid notification type: {$type}");
        }

        return $this->notifications()
            ->where('type', $type)
            ->latest()
            ->get();
    }

    /**
     * İstifadəçinin notification qəbul etmə tənzimləməsini yoxlamaq
     */
    public function canReceiveNotification(string $type, string $channel = 'in_app'): bool
    {
        $preference = $this->notificationPreferences()
            ->where('notification_type', $type)
            ->first();

        if (!$preference) {
            // Default tənzimləmələr
            return match($channel) {
                'email' => true,
                'sms' => false,
                'push' => true,
                'in_app' => true,
                default => true
            };
        }

        return match($channel) {
            'email' => $preference->email_enabled,
            'sms' => $preference->sms_enabled,
            'push' => $preference->push_enabled,
            'in_app' => $preference->in_app_enabled,
            default => true
        };
    }

    /**
     * İstifadəçinin aktiv cihazlarını əldə etmək
     */
    public function getActiveDevices(): Collection
    {
        return $this->notificationDevices()
            ->where('is_active', true)
            ->orderByDesc('last_used_at')
            ->get();
    }

    /**
     * İstifadəçinin notification tənzimləmələrini yaratmaq
     */
    public function createDefaultNotificationPreferences(): void
    {
        $defaultTypes = [
            'appointment',
            'review',
            'message',
            'system'
        ];

        foreach ($defaultTypes as $type) {
            $this->notificationPreferences()->updateOrCreate(
                [
                    'notification_type' => $type
                ],
                [
                    'email_enabled' => true,
                    'sms_enabled' => false,
                    'push_enabled' => true,
                    'in_app_enabled' => true,
                ]
            );
        }
    }

    /**
     * Müəyyən tip notification-ları say
     */
    public function countNotificationsByType(string $type): int
    {
        if (!NotificationTypeEnum::hasValue($type)) {
            throw new \InvalidArgumentException("Invalid notification type: {$type}");
        }

        return $this->notifications()
            ->where('type', $type)
            ->count();
    }

    /**
     * Müəyyən tip oxunmamış notification-ları say
     */
    public function countUnreadNotificationsByType(string $type): int
    {
        if (!NotificationTypeEnum::hasValue($type)) {
            throw new \InvalidArgumentException("Invalid notification type: {$type}");
        }

        return $this->notifications()
            ->where('type', $type)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Bugünkü notification-ları əldə et
     */
    public function getTodayNotifications(): Collection
    {
        return $this->notifications()
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Bu həftəki notification-ları əldə et
     */
    public function getThisWeekNotifications(): Collection
    {
        return $this->notifications()
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * İstifadəçinin ən çox aldığı notification növünü tap
     */
    public function getMostFrequentNotificationType(): ?string
    {
        $result = $this->notifications()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderByDesc('count')
            ->first();

        return $result?->type;
    }

    /**
     * Notification oxunma dərəcəsini hesabla
     */
    public function getNotificationReadRate(): float
    {
        $total = $this->notifications()->count();
        $read = $this->notifications()->whereNotNull('read_at')->count();

        return $total > 0 ? round(($read / $total) * 100, 2) : 0;
    }
}
