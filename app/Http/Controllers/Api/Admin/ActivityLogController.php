<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\ActivityLogResource;
use App\Services\Module\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends ApiController
{
    public function __construct(ActivityLogService $service)
    {
        parent::__construct($service, 'activity_log');
        $this->setResource(ActivityLogResource::class);
    }

    /**
     * Logların siyahısı
     */
    public function index(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $logs = $this->service->paginateAndFilter();

            return response()->json([
                'data' => $this->toResource($logs),
                'total' => $logs->total()
            ]);
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    public function statistics(Request $request): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $startDate = $request->get('start_date')
                ? Carbon::parse($request->get('start_date'))
                : Carbon::now()->subMonth();

            $endDate = $request->get('end_date')
                ? Carbon::parse($request->get('end_date'))
                : Carbon::now();

            $statistics = $this->service->getDetailedStatistics($startDate, $endDate);

            return response()->json($statistics);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Clean up old logs
     */
    public function cleanup(Request $request): JsonResponse
    {
        if ($this->authorizeAction('delete')) {
            $days = $request->get('days', 30);
            $count = $this->service->cleanup($days);

            return response()->json([
                'message' => "{$count} logs have been cleaned up",
                'count' => $count
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    public function commonRules(): array
    {
        return [
            // Add validation rules for store method
        ];
    }
}
