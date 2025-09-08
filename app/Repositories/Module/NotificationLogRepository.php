<?php

namespace App\Repositories\Module;

use App\Models\NotificationLog;
use App\Repositories\BaseRepository;
use App\Services\Filter\NotificationLogFilter;
use Carbon\Carbon;

class NotificationLogRepository extends BaseRepository
{
    public function __construct(NotificationLog $model)
    {
        parent::__construct($model);
        $this->setFilter(new NotificationLogFilter(request()));
        $this->with = ['user'];
    }

    /**
     * Uğurlu logları əldə etmək
     */
    public function findSuccessfulLogs(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('is_successful', true)
            ->orderByDesc('sent_at')
            ->get();
    }

    /**
     * Uğursuz logları əldə etmək
     */
    public function findFailedLogs(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('is_successful', false)
            ->orderByDesc('sent_at')
            ->get();
    }

    /**
     * Kanala görə logları əldə etmək
     */
    public function findByChannel(string $channel): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('channel', $channel)
            ->orderByDesc('sent_at')
            ->get();
    }

    /**
     * Notification növünə görə logları əldə etmək
     */
    public function findByNotificationType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('notification_type', $type)
            ->orderByDesc('sent_at')
            ->get();
    }

    /**
     * Son N gün ərzindəki logları əldə etmək
     */
    public function findRecentLogs(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('sent_at', '>=', now()->subDays($days))
            ->orderByDesc('sent_at')
            ->get();
    }

    /**
     * Müəyyən istifadəçi üçün logları əldə etmək
     */
    public function findByUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('user_id', $userId)
            ->orderByDesc('sent_at')
            ->get();
    }

    /**
     * Alıcıya görə logları axtarmaq
     */
    public function findByRecipient(string $recipient): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('recipient', 'like', "%{$recipient}%")
            ->orderByDesc('sent_at')
            ->get();
    }

    /**
     * Template koduna görə logları əldə etmək
     */
    public function findByTemplateCode(string $templateCode): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('template_code', $templateCode)
            ->orderByDesc('sent_at')
            ->get();
    }

    /**
     * Tarix aralığında logları əldə etmək
     */
    public function findBetweenDates(Carbon $startDate, Carbon $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->whereBetween('sent_at', [$startDate, $endDate])
            ->orderByDesc('sent_at')
            ->get();
    }

    /**
     * Bu günün logları
     */
    public function findTodayLogs(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->whereDate('sent_at', today())
            ->orderByDesc('sent_at')
            ->get();
    }

    /**
     * Bu həftənin logları
     */
    public function findThisWeekLogs(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->whereBetween('sent_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])
            ->orderByDesc('sent_at')
            ->get();
    }

    /**
     * Bu ayın logları
     */
    public function findThisMonthLogs(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->whereMonth('sent_at', now()->month)
            ->whereYear('sent_at', now()->year)
            ->orderByDesc('sent_at')
            ->get();
    }

    /**
     * Xəta mesajına görə qruplaşdırılmış statistika
     */
    public function getErrorStatistics(): \Illuminate\Support\Collection
    {
        return $this->model->where('is_successful', false)
            ->whereNotNull('error_message')
            ->selectRaw('error_message, channel, COUNT(*) as count')
            ->groupBy('error_message', 'channel')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Kanal üzrə statistika
     */
    public function getChannelStatistics(): \Illuminate\Support\Collection
    {
        return $this->model->selectRaw('
            channel,
            COUNT(*) as total,
            SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed
        ')
            ->groupBy('channel')
            ->orderBy('channel')
            ->get();
    }

    /**
     * Gündəlik statistika
     */
    public function getDailyStatistics(int $days = 30): \Illuminate\Support\Collection
    {
        $startDate = now()->subDays($days)->startOfDay();

        return $this->model->selectRaw('
            DATE(sent_at) as date,
            channel,
            COUNT(*) as total,
            SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed
        ')
            ->where('sent_at', '>=', $startDate)
            ->groupBy('date', 'channel')
            ->orderBy('date')
            ->get();
    }

    /**
     * En çox xəta verən alıcılar
     */
    public function getMostFailedRecipients(int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->model->where('is_successful', false)
            ->selectRaw('recipient, channel, COUNT(*) as failed_count')
            ->groupBy('recipient', 'channel')
            ->orderByDesc('failed_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Template performansı
     */
    public function getTemplatePerformance(): \Illuminate\Support\Collection
    {
        return $this->model->whereNotNull('template_code')
            ->selectRaw('
                template_code,
                COUNT(*) as total_usage,
                SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful_count,
                SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed_count
            ')
            ->groupBy('template_code')
            ->orderByDesc('total_usage')
            ->get();
    }

    /**
     * Saatlıq dağılım statistikası
     */
    public function getHourlyDistribution(): \Illuminate\Support\Collection
    {
        return $this->model->selectRaw('
            HOUR(sent_at) as hour,
            COUNT(*) as count,
            SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful
        ')
            ->where('sent_at', '>=', now()->subWeek())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    /**
     * Filter məlumatları
     */
    public function filters(): array
    {
        return [
            'channels' => $this->model->select('channel')
                ->distinct()
                ->orderBy('channel')
                ->pluck('channel')
                ->toArray(),
            'notification_types' => $this->model->select('notification_type')
                ->distinct()
                ->orderBy('notification_type')
                ->pluck('notification_type')
                ->toArray(),
            'template_codes' => $this->model->whereNotNull('template_code')
                ->select('template_code')
                ->distinct()
                ->orderBy('template_code')
                ->pluck('template_code')
                ->toArray()
        ];
    }

    /**
     * Köhnə logları silmə üçün sorğu
     */
    public function deleteOldLogs(int $days = 90, bool $keepFailed = true): int
    {
        $cutoffDate = now()->subDays($days);

        $query = $this->model->where('sent_at', '<', $cutoffDate);

        if ($keepFailed) {
            $query->where('is_successful', true);
        }

        return $query->delete();
    }

    /**
     * Bulk log yaratmaq
     */
    public function createBulkLogs(array $logsData): bool
    {
        try {
            $this->model->insert($logsData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
