<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\NotificationLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup
                           {--days=90 : Neçə gün əvvəlki məlumatları silmək}
                           {--keep-failed : Uğursuz log-ları saxlamaq}
                           {--notifications : Yalnız notification-ları təmizləmək}
                           {--logs : Yalnız log-ları təmizləmək}
                           {--dry-run : Test run without actually deleting}
                           {--force : Təsdiq olmadan silmək}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Köhnə notification və log məlumatlarını təmizləmək';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $keepFailed = $this->option('keep-failed');
        $notificationsOnly = $this->option('notifications');
        $logsOnly = $this->option('logs');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($days < 7) {
            $this->error('Təhlükəsizlik üçün minimum 7 gün tələb olunur');
            return Command::FAILURE;
        }

        $cutoffDate = now()->subDays($days);

        $this->info("Cleanup əməliyyatı başladılır...");
        $this->info("Tarix: {$cutoffDate->format('Y-m-d H:i:s')} ({$days} gün əvvəl)");

        if ($keepFailed) {
            $this->info("Uğursuz log-lar saxlanılacaq");
        }

        if ($dryRun) {
            $this->warn("TEST REJİMİ - Heç nə silinməyəcək");
        }

        try {
            $stats = [
                'notifications_deleted' => 0,
                'logs_deleted' => 0,
                'total_size_freed' => 0
            ];

            // Təsdiq alınması (force olmadıqda)
            if (!$force && !$dryRun) {
                if (!$this->confirm("Bu əməliyyat geri alına bilməz. Davam etmək istəyirsiniz?")) {
                    $this->info("Əməliyyat ləğv edildi");
                    return Command::SUCCESS;
                }
            }

            DB::beginTransaction();

            // Notification-ları təmizləmək
            if (!$logsOnly) {
                $stats['notifications_deleted'] = $this->cleanupNotifications($cutoffDate, $dryRun);
            }

            // Log-ları təmizləmək
            if (!$notificationsOnly) {
                $stats['logs_deleted'] = $this->cleanupLogs($cutoffDate, $keepFailed, $dryRun);
            }

            if (!$dryRun) {
                DB::commit();

                // Vacuum/Optimize tables
                $this->optimizeTables();
            } else {
                DB::rollBack();
            }

            $this->displayResults($stats, $dryRun);

            // Log əməliyyatı
            Log::info('Notification cleanup completed', [
                'days' => $days,
                'dry_run' => $dryRun,
                'keep_failed' => $keepFailed,
                'stats' => $stats
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Cleanup zamanı xəta: ' . $e->getMessage());

            Log::error('Notification cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Köhnə notification-ları silmək
     */
    protected function cleanupNotifications(\Carbon\Carbon $cutoffDate, bool $dryRun): int
    {
        $this->info("Notification-lar yoxlanılır...");

        $query = Notification::where('created_at', '<', $cutoffDate)
            ->where('is_sent', true); // Yalnız göndərilmiş notification-ları sil

        $count = $query->count();

        if ($count > 0) {
            $this->info("Silinəcək notification sayı: {$count}");

            if (!$dryRun) {
                $query->delete();
                $this->info("✅ {$count} notification silindi");
            }
        } else {
            $this->info("Silinəcək notification tapılmadı");
        }

        return $count;
    }

    /**
     * Köhnə log-ları silmək
     */
    protected function cleanupLogs(\Carbon\Carbon $cutoffDate, bool $keepFailed, bool $dryRun): int
    {
        $this->info("Notification log-lar yoxlanılır...");

        $query = NotificationLog::where('sent_at', '<', $cutoffDate);

        if ($keepFailed) {
            $query->where('is_successful', true);
            $this->info("Yalnız uğurlu log-lar silinəcək");
        }

        $count = $query->count();

        if ($count > 0) {
            $this->info("Silinəcək log sayı: {$count}");

            if (!$dryRun) {
                $query->delete();
                $this->info("✅ {$count} log silindi");
            }
        } else {
            $this->info("Silinəcək log tapılmadı");
        }

        return $count;
    }

    /**
     * Cədvəlləri optimize etmək
     */
    protected function optimizeTables(): void
    {
        $this->info("Cədvəllər optimize edilir...");

        try {
            // MySQL üçün
            if (config('database.default') === 'mysql') {
                DB::statement('OPTIMIZE TABLE notifications');
                DB::statement('OPTIMIZE TABLE notification_logs');
                $this->info("✅ MySQL cədvəlləri optimize edildi");
            }
        } catch (\Exception $e) {
            $this->warn("Cədvəl optimizasiyası uğursuz: " . $e->getMessage());
        }
    }

    /**
     * Nəticələri göstərmək
     */
    protected function displayResults(array $stats, bool $dryRun): void
    {
        $this->newLine();
        $this->info("=== CLEANUP NƏTİCƏLƏRİ ===");

        if ($dryRun) {
            $this->warn("TEST REJİMİ - Heç nə silinmədi");
        }

        $this->table(
            ['Məlumat Növü', 'Silinən Say'],
            [
                ['Notification-lar', number_format($stats['notifications_deleted'])],
                ['Log-lar', number_format($stats['logs_deleted'])],
                ['Ümumi', number_format($stats['notifications_deleted'] + $stats['logs_deleted'])]
            ]
        );

        // Cari statistikalar
        $this->newLine();
        $this->info("=== CARİ STATİSTİKALAR ===");

        $currentStats = [
            'notifications_count' => Notification::count(),
            'logs_count' => NotificationLog::count(),
            'sent_notifications' => Notification::where('is_sent', true)->count(),
            'failed_logs' => NotificationLog::where('is_successful', false)->count()
        ];

        $this->table(
            ['Məlumat', 'Say'],
            [
                ['Ümumi Notification-lar', number_format($currentStats['notifications_count'])],
                ['Göndərilmiş Notification-lar', number_format($currentStats['sent_notifications'])],
                ['Ümumi Log-lar', number_format($currentStats['logs_count'])],
                ['Uğursuz Log-lar', number_format($currentStats['failed_logs'])]
            ]
        );

        $this->newLine();
        $this->info("Cleanup əməliyyatı tamamlandı!");
    }

    /**
     * Disk yerinin hesablanması
     */
    protected function calculateTableSizes(): array
    {
        try {
            if (config('database.default') === 'mysql') {
                $sizes = DB::select("
                    SELECT
                        table_name,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                    FROM information_schema.TABLES
                    WHERE table_schema = DATABASE()
                    AND table_name IN ('notifications', 'notification_logs')
                ");

                return collect($sizes)->pluck('size_mb', 'table_name')->toArray();
            }
        } catch (\Exception $e) {
            // Xəta olduqda boş array qaytarırıq
        }

        return [];
    }
}
