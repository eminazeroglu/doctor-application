<?php

namespace App\Repositories\Module;

use App\Enums\NotificationPriorityEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Repositories\BaseRepository;
use App\Services\Filter\NotificationFilter;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationRepository extends BaseRepository
{
    public function __construct(Notification $model)
    {
        parent::__construct($model);
        $this->setFilter(new NotificationFilter(request()));
        $this->with = ['deliveries']; // Notification göndərmə məlumatlarını da əlavə edirik
    }

    /**
     * Notification yaratmaq.
     * parent::create metodunu override edirik çünki əlavə məntiq əlavə edirik.
     * @throws Exception
     */
    public function create(array $data): Notification
    {
        try {
            DB::beginTransaction();

            // Notification-ı yaradırıq
            $notification = parent::create($data);

            DB::commit();
            return $notification;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Notification göndərmə məlumatlarını qeyd edir.
     * Hansı kanallarla göndərilib, uğurlu olub-olmadığı və s.
     */
    public function logChannelDelivery(
        Notification $notification,
        string $channel,
        bool $success,
        ?string $error = null
    ): void {
        NotificationDelivery::create([
            'notification_id' => $notification->id,
            'channel' => $channel,
            'success' => $success,
            'error' => $error,
            'delivered_at' => now()
        ]);
    }

    /**
     * Planlaşdırılmış notification-ları qaytarır.
     * Scheduled job tərəfindən istifadə olunur.
     */
    public function getScheduledNotifications(): Collection
    {
        return $this->model
            ->whereNotNull('send_at')
            ->where('send_at', '<=', now())
            ->get();
    }

    /**
     * Gözləyən (pending) notification-ları qaytarır.
     * Bu o notification-lardır ki, yaradılıb amma hələ göndərilməyib.
     */
    public function getPendingNotifications(): Collection
    {
        return $this->model
            ->whereNull('send_at')
            ->whereDoesntHave('deliveries')
            ->get();
    }

    /**
     * Uğursuz göndərilən notification-ları yenidən cəhd üçün qaytarır.
     * Retry job tərəfindən istifadə olunur.
     */
    public function getFailedNotifications(): Collection
    {
        return $this->model
            ->whereHas('deliveries', function ($query) {
                $query->where('success', false);
            })
            ->where('retry_count', '<', config('notifications.max_retries', 3))
            ->get();
    }

    /**
     * Bütün notification-ları oxunmuş kimi qeyd edir.
     */
    public function markAllAsRead(Model $notifiable): void
    {
        $this->model
            ->where([
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id
            ])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Notification göndərmə məlumatlarını qeyd edir.
     * Bir notification-ın hər kanalla bir dəfə göndərilməsi qeyd olunur.
     */
    public function logDelivery(
        Notification $notification,
        string $channel,
        bool $success,
        ?string $error = null
    ): NotificationDelivery {
        // Əgər bu kanalla artıq göndərilibsə, update edirik
        $delivery = $notification->deliveries()
            ->where('channel', $channel)
            ->first();

        if ($delivery) {
            $delivery->update([
                'success' => $success,
                'error' => $error,
                'delivered_at' => now()
            ]);
            return $delivery;
        }

        // Yeni delivery qeydi yaradırıq
        return NotificationDelivery::create([
            'notification_id' => $notification->id,
            'channel' => $channel,
            'success' => $success,
            'error' => $error,
            'delivered_at' => now()
        ]);
    }

    /**
     * Uğursuz göndərmələri olan notification-ları qaytarır
     */
    public function getFailedDeliveries(): Collection
    {
        return $this->model
            ->whereHas('deliveries', function ($query) {
                $query->where('success', false);
            })
            ->with('deliveries')
            ->get();
    }


    /**
     * Oxunmamış notification sayını qaytarır.
     */
    public function getUnreadCount(Model $notifiable): int
    {
        return $this->model
            ->where([
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id
            ])
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Köhnə notification-ları təmizləyir.
     * Məsələn: 30 gündən köhnə notification-ları silmək üçün.
     */
    public function cleanOldNotifications(int $days = 30): int
    {
        return $this->model
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Göndərmə statistikalarını qaytarır
     */
    public function getDeliveryStats(): array
    {
        $stats = NotificationDelivery::query()
            ->selectRaw('
                channel,
                COUNT(*) as total,
                SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed
            ')
            ->groupBy('channel')
            ->get();

        return [
            'total' => [
                'sent' => $stats->sum('total'),
                'successful' => $stats->sum('successful'),
                'failed' => $stats->sum('failed')
            ],
            'by_channel' => $stats->mapWithKeys(function ($stat) {
                return [$stat->channel => [
                    'sent' => $stat->total,
                    'successful' => $stat->successful,
                    'failed' => $stat->failed
                ]];
            })
        ];
    }

    /**
     * Filter metodlarını implement edirik.
     * NotificationFilter tərəfindən istifadə olunur.
     */
    public function filters(): array
    {
        return [
            'types' => $this->model->select('type')->distinct()->pluck('type'),
            'priorities' => [
                NotificationPriorityEnum::LOW,
                NotificationPriorityEnum::NORMAL,
                NotificationPriorityEnum::HIGH
            ],
            'status' => [
                'read' => 'Oxunmuş',
                'unread' => 'Oxunmamış'
            ],
            'date_range' => true
        ];
    }

    /**
     * Admin panel üçün son aktivlikləri qaytarır.
     * Bu metod son yaradılan, oxunan və planlaşdırılan bildirişləri qaytarır.
     */
    public function getRecentActivity(int $limit = 10): array
    {
        $lastCreated = $this->model
            ->with(['notifiable', 'deliveries'])
            ->latest()
            ->limit($limit)
            ->get();

        $lastRead = $this->model
            ->with(['notifiable', 'deliveries'])
            ->whereNotNull('read_at')
            ->orderByDesc('read_at')
            ->limit($limit)
            ->get();

        $scheduledNext = $this->model
            ->with(['notifiable', 'deliveries'])
            ->whereNotNull('send_at')
            ->where('send_at', '>', now())
            ->orderBy('send_at')
            ->limit($limit)
            ->get();

        return [
            'last_created' => $lastCreated,
            'last_read' => $lastRead,
            'scheduled_next' => $scheduledNext
        ];
    }

    /**
     * Admin üçün bildiriş statistikalarını qaytarır
     * Bu metod detallı statistika məlumatlarını əldə edir
     */
    public function getAdminStatistics(): array
    {
        $baseQuery = $this->model->query();

        // Ümumi statistika
        $totalStats = [
            'total' => $baseQuery->count(),
            'unread' => $baseQuery->whereNull('read_at')->count(),
            'scheduled' => $baseQuery->whereNotNull('send_at')->where('send_at', '>', now())->count()
        ];

        // Prioritet üzrə statistika
        $priorityStats = $baseQuery->selectRaw('
            priority,
            COUNT(*) as total,
            SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread
        ')
            ->groupBy('priority')
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->priority => [
                    'total' => $item->total,
                    'unread' => $item->unread
                ]
            ]);

        // Tip üzrə statistika
        $typeStats = $baseQuery->selectRaw('
            type,
            COUNT(*) as total,
            SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread
        ')
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->type => [
                    'total' => $item->total,
                    'unread' => $item->unread,
                    'description' => NotificationTypeEnum::getDescription($item->type)
                ]
            ]);

        return [
            'summary' => $totalStats,
            'by_priority' => $priorityStats,
            'by_type' => $typeStats,
            'delivery' => $this->getDeliveryStats() // mövcud metodu istifadə edirik
        ];
    }

    /**
     * İstifadəçinin bildirişlərini tarix aralığına görə qaytarır
     */
    public function getUserNotificationsByDateRange(
        Model $user,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): Collection {
        return $this->model
            ->where([
                'notifiable_type' => get_class($user),
                'notifiable_id' => $user->id
            ])
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('created_at', '<=', $endDate))
            ->with(['deliveries'])
            ->latest()
            ->get();
    }

    /**
     * Çoxlu bildiriş silmə
     * Admin panel üçün seçilmiş bildirişləri silmək üçün
     * @throws Exception
     */
    public function bulkDelete(array $ids): bool
    {
        try {
            DB::beginTransaction();

            // Əvvəlcə delivery məlumatlarını silirik
            NotificationDelivery::whereIn('notification_id', $ids)->delete();

            // Sonra bildirişləri silirik
            $this->model->whereIn('id', $ids)->delete();

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Sistem bildirişlərini yaratmaq
     * Admin panel üçün SYSTEM_ALERT tipində bildirişlər yaratmaq üçün
     * @throws Exception
     */
    public function createSystemNotifications(array $userIds, array $data): Collection
    {
        $notifications = collect();

        DB::beginTransaction();
        try {
            foreach ($userIds as $userId) {
                $notification = $this->create([
                    'notifiable_type' => User::class,
                    'notifiable_id' => $userId,
                    'type' => NotificationTypeEnum::SYSTEM_ALERT,
                    'data' => $data,
                    'priority' => $data['priority'] ?? NotificationPriorityEnum::NORMAL,
                    'send_at' => $data['send_at'] ?? null
                ]);

                $notifications->push($notification);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $notifications;
    }
}
