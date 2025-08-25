<?php

namespace App\Console\Commands;

use App\Services\Module\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:send-scheduled {--limit=100 : Maximum number of notifications to process}';

    /**
     * The console command description.
     */
    protected $description = 'Planlaşdırılmış notification-ları göndərir';

    /**
     * NotificationService instance
     */
    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Planlaşdırılmış notification-lar göndərilir...');

        try {
            $startTime = microtime(true);

            // Planlaşdırılmış notification-ları göndərmək
            $sentCount = $this->notificationService->sendScheduledNotifications();

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            if ($sentCount > 0) {
                $this->info("✅ {$sentCount} notification uğurla göndərildi");
                $this->info("⏱️  İcra müddəti: {$executionTime} saniyə");

                Log::info('Scheduled notifications sent successfully', [
                    'sent_count' => $sentCount,
                    'execution_time' => $executionTime
                ]);
            } else {
                $this->info('📭 Göndəriləcək planlaşdırılmış notification tapılmadı');
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Planlaşdırılmış notification-lar göndərilə bilmədi: ' . $e->getMessage());

            Log::error('Failed to send scheduled notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }
}
