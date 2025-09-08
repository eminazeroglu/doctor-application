<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\NotificationLogResource;
use App\Models\NotificationLog;
use App\Services\Module\NotificationLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotificationLogController extends ApiController
{
    public function __construct(NotificationLogService $service)
    {
        parent::__construct($service, 'notification_log');
        $this->setResource(NotificationLogResource::class);
    }

    /**
     * Notification log siyahısı
     */
    public function index(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $data = $this->service->paginateAndFilter();
            return response()->json([
                'data' => $this->toResource($data),
                'total' => $data->total()
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Filterlər üçün məlumatlar
     */
    public function filters(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $filters = [
                'channels' => collect(NotificationChannelEnum::getValues())->map(fn($channel) => [
                    'value' => $channel,
                    'label' => NotificationChannelEnum::getDescription($channel)
                ])->values(),
                'notification_types' => collect(NotificationTypeEnum::getValues())->map(fn($type) => [
                    'value' => $type,
                    'label' => NotificationTypeEnum::getDescription($type)
                ])->values(),
                'statuses' => [
                    ['value' => true, 'label' => 'Uğurlu'],
                    ['value' => false, 'label' => 'Uğursuz']
                ]
            ];

            return response()->json($filters);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Notification log statistikalar
     */
    public function statistics(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            try {
                $stats = $this->service->getDetailedStatistics();
                return response()->json($stats);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Statistika alınma xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Köhnə logları təmizləmək
     *
     * @throws ValidationException
     */
    public function cleanup(Request $request): JsonResponse
    {
        if ($this->authorizeAction('delete')) {
            $validatedData = $this->validateRequest($request, [
                'days' => 'nullable|integer|min:7|max:365',
                'keep_failed' => 'nullable|boolean',
                'dry_run' => 'nullable|boolean'
            ]);

            try {
                $days = $validatedData['days'] ?? 90; // Default 90 gün
                $keepFailed = $validatedData['keep_failed'] ?? true;
                $dryRun = $validatedData['dry_run'] ?? false;

                $result = $this->service->cleanup($days, $keepFailed, $dryRun);

                return response()->json([
                    'message' => $dryRun ? 'Silinəcək log sayı' : 'Loglar təmizləndi',
                    'deleted_count' => $result['deleted_count'],
                    'remaining_count' => $result['remaining_count'],
                    'dry_run' => $dryRun
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Log təmizləmə xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Kanal üzrə statistika
     */
    public function channelStats(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            try {
                $stats = $this->service->getChannelStatistics();
                return response()->json($stats);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Kanal statistikası alınma xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Gündəlik statistika
     */
    public function dailyStats(Request $request): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $request->validate([
                'days' => 'nullable|integer|min:1|max:90'
            ]);

            try {
                $days = $request->get('days', 30);
                $stats = $this->service->getDailyStatistics($days);
                return response()->json($stats);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Gündəlik statistika alınma xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Uğursuz notification-ların detallı məlumatı
     */
    public function failedDetails(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            try {
                $failedLogs = NotificationLog::where('is_successful', false)
                    ->select('error_message', 'channel', 'notification_type')
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy('error_message', 'channel', 'notification_type')
                    ->orderByDesc('count')
                    ->limit(20)
                    ->get();

                return response()->json([
                    'failed_details' => $failedLogs,
                    'total_failed' => NotificationLog::where('is_successful', false)->count()
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Uğursuz notification məlumatı alınma xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Export log məlumatları (CSV formatında)
     */
    public function export(Request $request): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'channel' => 'nullable|string|in:' . implode(',', NotificationChannelEnum::getValues()),
                'status' => 'nullable|boolean'
            ]);

            try {
                $filters = $request->only(['start_date', 'end_date', 'channel', 'status']);
                $exportUrl = $this->service->exportLogs($filters);

                return response()->json([
                    'message' => 'Export hazırdır',
                    'download_url' => $exportUrl,
                    'expires_at' => now()->addHours(24)->toIso8601String()
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Export xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Real-time notification göndərmə statusu
     */
    public function realtimeStatus(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            try {
                // Son 1 saat ərzində göndərilən notification-ların statusu
                $recentStats = NotificationLog::where('sent_at', '>=', now()->subHour())
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
                    });

                return response()->json([
                    'period' => 'Son 1 saat',
                    'stats_by_channel' => $recentStats,
                    'updated_at' => now()->toIso8601String()
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Real-time status alınma xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }
}
