<?php

namespace App\Repositories\Module;

use App\Enums\ActivityLogActionEnum;
use App\Enums\ActivityLogStatusEnum;
use App\Models\ActivityLog;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\ActivityLogRepositoryInterface;
use App\Services\Filter\ActivityLogFilter;
use Illuminate\Support\Facades\DB;

class ActivityLogRepository extends BaseRepository implements ActivityLogRepositoryInterface
{
    public function __construct(ActivityLog $model)
    {
        parent::__construct($model);
        $this->setFilter(new ActivityLogFilter(request()));
        $this->with = ['creator'];
    }

    public function filters(): array
    {
        $actions = collect(ActivityLogActionEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => ActivityLogActionEnum::getDescription($i)
        ]);
        $statuses = collect(ActivityLogStatusEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => ActivityLogStatusEnum::getDescription($i)
        ]);
        $methods = [
            ['id' => 'GET', 'name' => 'GET'],
            ['id' => 'POST', 'name' => 'POST'],
            ['id' => 'PUT', 'name' => 'PUT'],
            ['id' => 'DELETE', 'name' => 'DELETE'],
            ['id' => 'PATCH', 'name' => 'PATCH'],
        ];
        return [
            'actions' => $actions,
            'statuses' => $statuses,
            'methods' => $methods,
        ];
    }

    /**
     * @throws \Exception
     */
    public function createLog(array $data): ActivityLog
    {
        try {
            DB::beginTransaction();

            $log = $this->model->create($data);

            DB::commit();
            return $log;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function cleanOldLogs(int $days = 30): int
    {
        return $this->model
            ->when($days > 0, function ($q) use ($days) {
                $q->where('created_at', '<', now()->subDays($days));
            })
            ->forceDelete();
    }

    protected function applyFilters($query, array $filters)
    {
        // Tarix aralığı filtri
        if (!empty($filters['date_range'])) {
            $query->whereBetween('created_at', [
                $filters['date_range']['start'],
                $filters['date_range']['end']
            ]);
        }

        // IP ünvan filtri
        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        // URL filtri
        if (!empty($filters['url'])) {
            $query->where('url', 'like', "%{$filters['url']}%");
        }

        // Method filtri
        if (!empty($filters['method'])) {
            $query->where('method', $filters['method']);
        }

        // Axtarış filtri
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('ip_address', 'like', "%{$filters['search']}%")
                    ->orWhere('url', 'like', "%{$filters['search']}%")
                    ->orWhere('error_message', 'like', "%{$filters['search']}%");
            });
        }

        return $query;
    }

}
