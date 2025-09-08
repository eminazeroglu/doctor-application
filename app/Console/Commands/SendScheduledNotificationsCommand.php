<?php

namespace App\Console\Commands;

use App\Services\Module\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SendScheduledNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-scheduled
                           {--dry-run : Test run without actually sending}
                           {--limit=100 : Maximum number of notifications to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Planlaşdırılmış notification-ları göndərmək';

    protected NotificationService $notificationService;

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
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('Planlaşdırılmış notification-lar yoxlanılır...');

        try {
            if ($dryRun) {
                $count = $this->checkScheduledNotifications($limit);
                $this->info("Test rejimi: {$count} notification göndərilməyə hazırdır");
            } else {
                $count = $this->notificationService->sendScheduledNotifications();
                $this->info("{$count} planlaşdırılmış notification uğurla göndərildi");

                Log::info('Scheduled notifications sent', [
                    'count' => $count,
                    'command' => 'notifications:send-scheduled'
                ]);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Xəta baş verdi: ' . $e->getMessage());

            Log::error('Scheduled notifications command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return CommandAlias::FAILURE;
        }
    }

    /**
     * Göndərilməyə hazır notification-ları yoxlayır (dry-run üçün)
     */
    protected function checkScheduledNotifications(int $limit): int
    {
        return \App\Models\Notification::where('is_sent', false)
            ->where(function($q) {
                $q->whereNull('send_at')
                    ->orWhere('send_at', '<=', now());
            })
            ->limit($limit)
            ->count();
    }
}
