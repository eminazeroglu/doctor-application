<?php

namespace App\Jobs;

use App\Services\Module\NotificationService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBulkNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $userIds;
    public string $type;
    public array $data;

    public int $tries = 1;
    public int $timeout = 300; // 5 dəqiqə

    public function __construct(array $userIds, string $type, array $data)
    {
        $this->userIds = $userIds;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * @throws Exception
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            $result = $notificationService->sendBulk($this->userIds, $this->type, $this->data);

            Log::info('Bulk notification processed', [
                'total_users' => count($this->userIds),
                'successful' => $result['successful'],
                'failed' => $result['failed'],
                'type' => $this->type
            ]);

        } catch (Exception $e) {
            Log::error('Bulk notification job failed', [
                'user_count' => count($this->userIds),
                'type' => $this->type,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk notification job failed permanently', [
            'user_count' => count($this->userIds),
            'type' => $this->type,
            'error' => $exception->getMessage()
        ]);
    }
}
