<?php

namespace App\Traits\Model;

use App\Enums\NotificationPriorityEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Services\Module\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasNotification
{
    /**
     * Model-in bütün notification-larını əldə etmək üçün relationship
     * MorphMany relationship istifadə edərək polymorphic əlaqə qururuq
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Sadəcə oxunmamış notification-ları əldə etmək üçün relationship
     * notifications() relationship-ini filter edərək oxunmamışları alırıq
     */
    public function unreadNotifications(): MorphMany
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * Yüksək prioritetli notification-ları əldə etmək üçün relationship
     * notifications() relationship-ini priority-ə görə filter edirik
     */
    public function highPriorityNotifications(): MorphMany
    {
        return $this->notifications()
            ->where('priority', NotificationPriorityEnum::HIGH)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Planlaşdırılmış notification-ları əldə etmək üçün relationship
     * Gələcək tarixə planlaşdırılmış notification-ları qaytarır
     */
    public function scheduledNotifications(): MorphMany
    {
        return $this->notifications()
            ->whereNotNull('send_at')
            ->where('send_at', '>', now())
            ->orderBy('send_at', 'asc');
    }

    /**
     * Model-ə notification göndərmək üçün əsas method
     * NotificationService-i istifadə edərək notification yaradır
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
        // Default olaraq bütün kanalları qəbul edirik
        return match($channel) {
            'email' => !empty($this->email),
            'telegram' => !empty($this->telegram_id),
            'push' => !empty($this->push_token),
            default => false
        };
    }


    /**
     * Model üçün gələcək tarixə notification planlaşdırmaq
     * Müəyyən tarixdə göndəriləcək notification yaradır
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
     * Modelin bütün oxunmamış notification-larını oxunmuş edir
     */
    public function markAllNotificationsAsRead(): void
    {
        app(NotificationService::class)->markAllAsRead($this);
    }

    /**
     * Oxunmamış notification sayını əldə etmək
     * Modelin oxunmamış notification-larının sayını qaytarır
     */
    public function getUnreadNotificationCount(): int
    {
        return app(NotificationService::class)->getUnreadCount($this);
    }

    /**
     * Notification statistikalarını əldə etmək
     * Modelin notification-ları haqqında ümumi statistika qaytarır
     */
    public function getNotificationStats(): array
    {
        return app(NotificationService::class)->getStats($this);
    }

    /**
     * Notification göndərmə məlumatlarını əldə etmək üçün relationship
     */
    public function notificationDeliveries(): HasManyThrough
    {
        return $this->hasManyThrough(
            NotificationDelivery::class,
            Notification::class,
            'notifiable_id',
            'notification_id'
        )->where('notifiable_type', self::class);
    }

    /**
     * Notification göndərmə statistikalarını əldə etmək
     */
    public function getNotificationDeliveryStats(): array
    {
        return $this->notificationDeliveries()
            ->selectRaw('
            channel,
            COUNT(*) as total,
            SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed
        ')
            ->groupBy('channel')
            ->get()
            ->mapWithKeys(function ($stat) {
                return [$stat->channel => [
                    'total' => $stat->total,
                    'successful' => $stat->successful,
                    'failed' => $stat->failed
                ]];
            })
            ->toArray();
    }

    /**
     * Son notification-ları əldə etmək
     * Modelin son N sayda notification-larını qaytarır
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
     * Verilən tipə uyğun notification-ları qaytarır
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
}
