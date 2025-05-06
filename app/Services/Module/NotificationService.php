<?php

namespace App\Services\Module;

use App\Contracts\NotificationChannel;
use App\Enums\NotificationPriorityEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\Module\NotificationRepository;
use App\Services\BaseCrudService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotificationService extends BaseCrudService
{
    /**
     * Notification kanallarını saxlayan array.
     * Hər bir kanal NotificationChannel interface-ni implement etməlidir.
     * Məsələn: ['email' => EmailChannel, 'telegram' => TelegramChannel]
     */
    protected array $channels = [];

    public function __construct(NotificationRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Yeni notification kanalı əlavə edir.
     * Service provider-da istifadə olunur.
     *
     * @param string $name Kanalın adı (email, telegram, push və s.)
     * @param NotificationChannel $channel Kanal instance-ı
     */
    public function registerChannel(string $name, NotificationChannel $channel): void
    {
        $this->channels[$name] = $channel;
    }

    /**
     * Notification yaradır və müxtəlif kanallara göndərir.
     *
     * @param Model $notifiable Notification alan model (User və s.)
     * @param string $type Notification növü (NotificationTypeEnum-dan)
     * @param array $data Əlavə məlumatlar
     * @param string|null $priority Prioritet (NotificationPriorityEnum-dan)
     * @param Carbon|null $sendAt Planlaşdırma tarixi
     *
     * @throws \InvalidArgumentException Notification növü səhv olduqda
     * @return Notification Yaradılan notification
     */
    public function send(
        Model $notifiable,
        string $type,
        array $data = [],
        ?string $priority = null,
        ?Carbon $sendAt = null
    ): Notification {
        // 1. Validation
        if (!NotificationTypeEnum::hasValue($type)) {
            throw new \InvalidArgumentException("Invalid notification type: {$type}");
        }

        // 2. Data hazırlığı
        $processedData = $this->prepareNotificationData($type, $data);
        $finalPriority = $priority ?? NotificationTypeEnum::getDefaultPriority($type);

        // 3. Notification yaratmaq
        $notification = $this->createNotification(
            notifiable: $notifiable,
            type: $type,
            data: $processedData,
            priority: $finalPriority,
            sendAt: $sendAt
        );

        // 4. Göndərmə prosesi
        if (!$sendAt || !$sendAt->isFuture()) {
            $this->sendToChannels($notifiable, $notification);
        }

        return $notification;
    }

    /**
     * Notification məlumatlarını hazırlayır.
     * Default data ilə custom datanı birləşdirir və placeholderləri əvəz edir.
     */
    protected function prepareNotificationData(string $type, array $data): array
    {
        // Default data strukturunu alırıq
        $defaultData = NotificationTypeEnum::getDefaultData($type);

        // Default data ilə custom datanı birləşdiririk
        $mergedData = array_merge($defaultData, $data);

        // Əgər mesaj varsa, placeholderləri əvəz edirik
        if (isset($mergedData['message'])) {
            $mergedData['message'] = $this->replacePlaceholders(
                message: $mergedData['message'],
                data: $data
            );
        }

        return $mergedData;
    }

    /**
     * Notification-ı databazada yaradır.
     */
    protected function createNotification(
        Model $notifiable,
        string $type,
        array $data,
        string $priority,
        ?Carbon $sendAt
    ): Notification {
        return $this->repository->create([
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
            'type' => $type,
            'data' => $data,
            'priority' => $priority,
            'send_at' => $sendAt
        ]);
    }

    /**
     * Notification-ı qeydiyyatdan keçmiş bütün kanallara göndərir.
     * Hər kanal üçün ayrıca try-catch bloku istifadə olunur ki,
     * bir kanalda xəta olsa digər kanallar işləməyə davam etsin.
     */
    protected function sendToChannels(Model $notifiable, Notification $notification): void
    {
        foreach ($this->channels as $channelName => $channel) {
            try {
                $sent = $channel->send($notifiable, $notification);

                // Göndərmə nəticəsini qeyd edirik
                $this->repository->logDelivery(
                    notification: $notification,
                    channel: $channelName,
                    success: $sent
                );
            } catch (\Exception $e) {
                // Xəta baş verdikdə qeyd edirik
                $this->repository->logDelivery(
                    notification: $notification,
                    channel: $channelName,
                    success: false,
                    error: $e->getMessage()
                );
            }
        }
    }

    /**
     * Notification mesajındakı placeholder-ləri real data ilə əvəz edir.
     * Həm sadə ({name}), həm də nested ({user.name}) placeholder-ləri dəstəkləyir.
     */
    protected function replacePlaceholders(string $message, array $data): string
    {
        return preg_replace_callback('/{([^}]+)}/', function($matches) use ($data) {
            $placeholder = $matches[1];

            // Nested placeholder (user.name)
            if (str_contains($placeholder, '.')) {
                return $this->getNestedValue($data, $placeholder);
            }

            // Sadə placeholder (name)
            return $data[$placeholder] ?? $matches[0];
        }, $message);
    }

    /**
     * Nested array-dən dot notation ilə dəyər əldə edir.
     * Məsələn: ['user' => ['name' => 'John']] array-indən 'user.name' ilə 'John' qaytarır.
     */
    protected function getNestedValue(array $data, string $key): string
    {
        $keys = explode('.', $key);
        $value = $data;

        foreach ($keys as $nestedKey) {
            if (!isset($value[$nestedKey])) {
                return '{' . $key . '}';
            }
            $value = $value[$nestedKey];
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    /**
     * Çoxlu sayda alıcıya eyni notification-ı göndərir.
     * Hər bir alıcı üçün ayrı notification yaradılır.
     */
    public function sendMultiple(Collection $notifiables, string $type, array $data = []): void
    {
        foreach ($notifiables as $notifiable) {
            $this->send($notifiable, $type, $data);
        }
    }

    /**
     * Notification-ı gələcək tarixə planlaşdırır.
     */
    public function schedule(
        Model $notifiable,
        string $type,
        Carbon $sendAt,
        array $data = []
    ): Notification {
        return $this->send(
            notifiable: $notifiable,
            type: $type,
            data: $data,
            sendAt: $sendAt
        );
    }

    /**
     * Planlaşdırılmış notification-ı ləğv edir.
     */
    public function cancelScheduled(int $id): void
    {
        $notification = $this->findById($id);

        if ($notification->send_at && $notification->send_at->isFuture()) {
            $notification->update(['send_at' => null]);
        }
    }

    /**
     * Notification-ı oxunmuş kimi qeyd edir.
     */
    public function markAsRead(int $id): void
    {
        $notification = $this->findById($id);
        $notification->markAsRead();
    }

    /**
     * Alıcının bütün notification-larını oxunmuş kimi qeyd edir.
     */
    public function markAllAsRead(Model $notifiable): void
    {
        $this->repository->markAllAsRead($notifiable);
    }

    /**
     * Alıcının oxunmamış notification sayını qaytarır.
     */
    public function getUnreadCount(Model $notifiable): int
    {
        return $this->repository->getUnreadCount($notifiable);
    }

    /**
     * Alıcının notification statistikasını qaytarır.
     * Ümumi say, oxunmamış say, yüksək prioritetli say və s.
     */
    public function getStats(Model $notifiable): array
    {
        $notifications = $this->repository->findWhere([
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id
        ]);

        return [
            'total' => $notifications->count(),
            'unread' => $notifications->whereNull('read_at')->count(),
            'high_priority' => $notifications->where('priority', NotificationPriorityEnum::HIGH)->count(),
            'latest' => $notifications->sortByDesc('created_at')->first()?->created_at,
            'by_type' => $notifications->groupBy('type')
                ->map(fn($group) => $group->count()),
        ];
    }

    /**
     * Admin panel üçün yeni sistem bildirişi yaratma.
     * Bu metod mövcud 'send' metodunu istifadə edərək SYSTEM_ALERT tipində
     * bildirişlər yaradır və müvafiq kanallara göndərir.
     */
    public function createSystemNotifications(array $data): array
    {
        $users = User::query()
            ->whereIn('id', $data['user_ids'])
            ->where('status', 'active')
            ->get();

        $notifications = [];

        // Bildiriş məlumatlarını hazırlayırıq və NotificationTypeEnum-dakı
        // default data ilə birləşdiriləcək
        $notificationData = [
            'title' => $data['title'],
            'message' => $data['message'],
            'action_url' => $data['action_url'] ?? null,
            'admin_id' => auth()->id(),
            'admin_name' => auth()->user()->fullname,
            'created_at' => now(),
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]
        ];

        // Hər bir istifadəçi üçün mövcud send metodunu istifadə edirik
        foreach ($users as $user) {
            $notification = $this->send(
                notifiable: $user,
                type: NotificationTypeEnum::SYSTEM_ALERT,
                data: $notificationData,
                sendAt: isset($data['send_at']) ? Carbon::parse($data['send_at']) : null
            );

            $notifications[] = $notification;
        }

        return $notifications;
    }

    /**
     * İstifadəçi interfeysi üçün bildirişləri əldə etmə.
     * Bu metod mövcud repository metodlarını istifadə edərək
     * istifadəçinin bildirişlərini qaytarır.
     */
    public function getUserNotifications(
        Model $user,
        string $status = 'all',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $user->notifications();

        // Status filteri
        if ($status !== 'all') {
            $query = match($status) {
                'read' => $query->whereNotNull('read_at'),
                'unread' => $query->whereNull('read_at'),
                default => $query
            };
        }

        // Göndərmə məlumatlarını da əlavə edirik
        return $query->with(['deliveries'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Admin panel üçün ətraflı statistika.
     * Bu metod mövcud repository metodlarını istifadə edərək
     * sistemdəki bildirişlər haqqında ətraflı statistika qaytarır.
     */
    public function getAdminStats(): array
    {
        return [
            'total' => [
                'count' => $this->repository->count(),
                'unread' => $this->repository->whereNull('read_at')->count(),
                'scheduled' => $this->repository->whereNotNull('send_at')
                    ->where('send_at', '>', now())
                    ->count()
            ],
            'by_type' => $this->repository->groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->get(),
            'delivery_stats' => $this->repository->getDeliveryStats(),
            'recent_activity' => $this->repository->getRecentActivity()
        ];
    }

    /**
     * İstifadəçi bildiriş statistikası.
     * Bu metod mövcud getStats metodunu istifadə edərək
     * istifadəçinin bildirişləri haqqında statistika qaytarır.
     */
    public function getUserStats(Model $user): array
    {
        // Mövcud getStats metodunu istifadə edirik
        $stats = $this->getStats($user);

        // İstifadəçi interfeysi üçün əlavə məlumatlar
        return array_merge($stats, [
            'delivery_channels' => $user->notificationDeliveries()
                ->selectRaw('channel, COUNT(*) as count')
                ->groupBy('channel')
                ->get()
        ]);
    }
}
