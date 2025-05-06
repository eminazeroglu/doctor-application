<?php

namespace App\Traits\Model;

use App\Enums\ActivityLogActionEnum;
use App\Services\Module\ActivityLogService;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HasLoggable
{
    protected static function bootHasLoggable(): void
    {
        static::created(function ($model) {
            $model->logActivity(
                ActivityLogActionEnum::CREATED,
                null,
                $model->getAttributes()
            );
        });

        static::updated(function ($model) {
            if ($model->isDirty()) {
                $model->logActivity(
                    ActivityLogActionEnum::UPDATED,
                    $model->getOriginal(),
                    $model->getChanges()
                );
            }
        });

        static::deleted(function ($model) {
            $model->logActivity(
                ActivityLogActionEnum::DELETED,
                $model->getAttributes(),
                null
            );
        });

        if (self::usesSoftDeletes()) {
            static::restored(function ($model) {
                $model->logActivity(
                    ActivityLogActionEnum::RESTORED,
                    null,
                    $model->getAttributes()
                );
            });
        }
    }

    /**
     * Model-in SoftDeletes istifadə edib-etmədiyini yoxlayır
     */
    protected static function usesSoftDeletes(): bool
    {
        return in_array(
            SoftDeletes::class,
            class_uses_recursive(static::class)
        );
    }

    public function logActivity(
        string $action,
        ?array $oldData = null,
        ?array $newData = null,
        array  $additionalData = []
    ): void
    {
        try {
            DB::beginTransaction();

            $logService = app(ActivityLogService::class);

            $contextData = [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id(),
                'timestamp' => now()->toDateTimeString()
            ];

            $logService->log(
                action: $action,
                model: $this,
                oldData: $this->filterSensitiveData($oldData),
                newData: $this->filterSensitiveData($newData),
                additionalData: array_merge($contextData, $additionalData)
            );

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Activity logging failed', [
                'model' => get_class($this),
                'id' => $this->id,
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function filterSensitiveData(?array $data): ?array
    {
        if (!$data) {
            return null;
        }

        $sensitiveFields = [
            'password',
            'remember_token',
            'api_token',
            'secret',
            'key'
        ];

        return array_filter(
            $data,
            fn($key) => !in_array($key, $sensitiveFields),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function activities()
    {
        return $this->morphMany('App\Models\ActivityLog', 'model');
    }

    public static function logCustomActivity(
        string $action,
        array  $data,
        array  $additionalData = []
    ): void
    {
        $model = new static;
        $model->logActivity($action, null, $data, $additionalData);
    }
}
