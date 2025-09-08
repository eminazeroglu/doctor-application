<?php

namespace App\Services\Module;

use App\Repositories\Module\NotificationLogRepository;
use App\Services\BaseCrudService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NotificationLogService extends BaseCrudService
{
    public function __construct(NotificationLogRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Detallı statistikalar
     */
    public function getDetailedStatistics(): array
    {
        return [
            'overview' => $this->getOverviewStats(),
            'by_channel' => $this->getChannelStatistics(),
            'by_type' => $this->getTypeStatistics(),
            'success_rate' => $this->getSuccessRate(),
            'recent_activity' => $this->getRecentActivity()
        ];
    }

    /**
     * Ümumi statistikalar
     */
    protected function getOverviewStats(): array
    {
        $total = $this->repository->findAll()->count();
        $successful = $this->repository->findWhere(['is_successful' => true])->count();
        $failed = $total - $successful;

        return [
            'total_notifications' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'today' => $this->getTodayStats(),
            'this_week' => $this->getThisWeekStats(),
            'this_month' => $this->getThisMonthStats()
        ];
    }

    /**
     * Bugünkü statistikalar
     */
    protected function getTodayStats(): array
    {
        $today = DB::table('notification_logs')
            ->whereDate('sent_at', today())
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed
            ')
            ->first();

        return [
            'total' => $today->total ?? 0,
            'successful' => $today->successful ?? 0,
            'failed' => $today->failed ?? 0,
            'success_rate' => $today->total > 0 ? round(($today->successful / $today->total) * 100, 2) : 0
        ];
    }

    /**
     * Bu həftəki statistikalar
     */
    protected function getThisWeekStats(): array
    {
        $week = DB::table('notification_logs')
            ->whereBetween('sent_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed
            ')
            ->first();

        return [
            'total' => $week->total ?? 0,
            'successful' => $week->successful ?? 0,
            'failed' => $week->failed ?? 0,
            'success_rate' => $week->total > 0 ? round(($week->successful / $week->total) * 100, 2) : 0
        ];
    }

    /**
     * Bu aylıq statistikalar
     */
    protected function getThisMonthStats(): array
    {
        $month = DB::table('notification_logs')
            ->whereMonth('sent_at', now()->month)
            ->whereYear('sent_at', now()->year)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed
            ')
            ->first();

        return [
            'total' => $month->total ?? 0,
            'successful' => $month->successful ?? 0,
            'failed' => $month->failed ?? 0,
            'success_rate' => $month->total > 0 ? round(($month->successful / $month->total) * 100, 2) : 0
        ];
    }

    /**
     * Kanal üzrə statistikalar
     */
    public function getChannelStatistics(): array
    {
        $stats = DB::table('notification_logs')
            ->selectRaw('
                channel,
                COUNT(*) as total,
                SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed
            ')
            ->groupBy('channel')
            ->get();

        return $stats->mapWithKeys(function ($stat) {
            return [$stat->channel => [
                'total' => $stat->total,
                'successful' => $stat->successful,
                'failed' => $stat->failed,
                'success_rate' => $stat->total > 0 ? round(($stat->successful / $stat->total) * 100, 2) : 0
            ]];
        })->toArray();
    }

    /**
     * Notification növü üzrə statistikalar
     */
    protected function getTypeStatistics(): array
    {
        $stats = DB::table('notification_logs')
            ->selectRaw('
                notification_type,
                COUNT(*) as total,
                SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed
            ')
            ->groupBy('notification_type')
            ->orderByDesc('total')
            ->get();

        return $stats->map(function ($stat) {
            return [
                'type' => $stat->notification_type,
                'total' => $stat->total,
                'successful' => $stat->successful,
                'failed' => $stat->failed,
                'success_rate' => $stat->total > 0 ? round(($stat->successful / $stat->total) * 100, 2) : 0
            ];
        })->toArray();
    }

    /**
     * Ümumi uğur dərəcəsi
     */
    protected function getSuccessRate(): array
    {
        $total = DB::table('notification_logs')->count();
        $successful = DB::table('notification_logs')->where('is_successful', true)->count();

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $total - $successful,
            'rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0
        ];
    }

    /**
     * Son aktivlik
     */
    protected function getRecentActivity(): array
    {
        return DB::table('notification_logs')
            ->select('channel', 'notification_type', 'is_successful', 'sent_at')
            ->orderByDesc('sent_at')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Gündəlik statistikalar
     */
    public function getDailyStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        $stats = DB::table('notification_logs')
            ->selectRaw('
                DATE(sent_at) as date,
                channel,
                COUNT(*) as total,
                SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed
            ')
            ->whereBetween('sent_at', [$startDate, $endDate])
            ->groupBy('date', 'channel')
            ->orderBy('date')
            ->get();

        // Günlər üzrə qruplaşdırma
        $dailyStats = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $dailyStats[$dateStr] = [
                'date' => $dateStr,
                'channels' => [],
                'total' => 0,
                'successful' => 0,
                'failed' => 0
            ];
        }

        foreach ($stats as $stat) {
            if (!isset($dailyStats[$stat->date]['channels'][$stat->channel])) {
                $dailyStats[$stat->date]['channels'][$stat->channel] = [
                    'total' => 0,
                    'successful' => 0,
                    'failed' => 0
                ];
            }

            $dailyStats[$stat->date]['channels'][$stat->channel]['total'] += $stat->total;
            $dailyStats[$stat->date]['channels'][$stat->channel]['successful'] += $stat->successful;
            $dailyStats[$stat->date]['channels'][$stat->channel]['failed'] += $stat->failed;

            $dailyStats[$stat->date]['total'] += $stat->total;
            $dailyStats[$stat->date]['successful'] += $stat->successful;
            $dailyStats[$stat->date]['failed'] += $stat->failed;
        }

        return array_values($dailyStats);
    }

    /**
     * Köhnə logları təmizləmək
     */
    public function cleanup(int $days = 90, bool $keepFailed = true, bool $dryRun = false): array
    {
        $cutoffDate = now()->subDays($days);

        $query = DB::table('notification_logs')
            ->where('sent_at', '<', $cutoffDate);

        if ($keepFailed) {
            $query->where('is_successful', true);
        }

        // Silinəcək sayını hesabla
        $deleteCount = $query->count();

        if (!$dryRun) {
            // Həqiqətən sil
            $query->delete();
        }

        $remainingCount = DB::table('notification_logs')->count();

        return [
            'deleted_count' => $dryRun ? 0 : $deleteCount,
            'would_delete_count' => $dryRun ? $deleteCount : 0,
            'remaining_count' => $remainingCount,
            'cutoff_date' => $cutoffDate->toDateString()
        ];
    }

    /**
     * Log məlumatlarını export etmək
     */
    public function exportLogs(array $filters = []): string
    {
        $query = DB::table('notification_logs')
            ->leftJoin('users', 'notification_logs.user_id', '=', 'users.id')
            ->select([
                'notification_logs.id',
                'notification_logs.channel',
                'notification_logs.recipient',
                'notification_logs.notification_type',
                'notification_logs.subject',
                'notification_logs.is_successful',
                'notification_logs.error_message',
                'notification_logs.sent_at',
                'users.name as user_name',
                'users.email as user_email'
            ]);

        // Filterləri tətbiq et
        if (isset($filters['start_date'])) {
            $query->whereDate('notification_logs.sent_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('notification_logs.sent_at', '<=', $filters['end_date']);
        }

        if (isset($filters['channel'])) {
            $query->where('notification_logs.channel', $filters['channel']);
        }

        if (isset($filters['status'])) {
            $query->where('notification_logs.is_successful', $filters['status']);
        }

        $logs = $query->orderByDesc('notification_logs.sent_at')->get();

        // CSV yaratmaq
        $fileName = 'notification_logs_' . now()->format('Y_m_d_H_i_s') . '.csv';
        $filePath = 'exports/' . $fileName;

        $csv = "ID,Kanal,Alıcı,Növ,Mövzu,Status,Xəta,Göndərilmə vaxtı,İstifadəçi adı,İstifadəçi email\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $log->id,
                $log->channel,
                $log->recipient,
                $log->notification_type,
                str_replace(['"', ',', "\n"], ['""', ';', ' '], $log->subject ?? ''),
                $log->is_successful ? 'Uğurlu' : 'Uğursuz',
                str_replace(['"', ',', "\n"], ['""', ';', ' '], $log->error_message ?? ''),
                $log->sent_at,
                $log->user_name ?? '',
                $log->user_email ?? ''
            );
        }

        Storage::put($filePath, $csv);

        return Storage::url($filePath);
    }

    /**
     * Xəta analizini əldə etmək
     */
    public function getErrorAnalysis(): array
    {
        $errors = DB::table('notification_logs')
            ->where('is_successful', false)
            ->whereNotNull('error_message')
            ->selectRaw('error_message, channel, COUNT(*) as count')
            ->groupBy('error_message', 'channel')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        return $errors->map(function ($error) {
            return [
                'error_message' => $error->error_message,
                'channel' => $error->channel,
                'count' => $error->count
            ];
        })->toArray();
    }

    /**
     * Performance analizi
     */
    public function getPerformanceAnalysis(): array
    {
        // Saatlıq dağılım
        $hourlyStats = DB::table('notification_logs')
            ->selectRaw('HOUR(sent_at) as hour, COUNT(*) as count')
            ->where('sent_at', '>=', now()->subWeek())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        // Həftənin günləri üzrə dağılım
        $weeklyStats = DB::table('notification_logs')
            ->selectRaw('DAYOFWEEK(sent_at) as day_of_week, COUNT(*) as count')
            ->where('sent_at', '>=', now()->subMonth())
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get()
            ->pluck('count', 'day_of_week')
            ->toArray();

        return [
            'hourly_distribution' => $hourlyStats,
            'weekly_distribution' => $weeklyStats,
            'peak_hour' => array_keys($hourlyStats, max($hourlyStats))[0] ?? null,
            'peak_day' => array_keys($weeklyStats, max($weeklyStats))[0] ?? null
        ];
    }
}
