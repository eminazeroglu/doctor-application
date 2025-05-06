<?php

namespace App\Services\Module;

use App\Enums\ActivityLogStatusEnum;
use App\Repositories\Module\ActivityLogRepository;
use App\Services\BaseCrudService;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService extends BaseCrudService
{
    // Həssas məlumatların filtrlənməsi üçün siyahı
    protected array $sensitiveFields = [
        'password',
        'token',
        'remember_token',
        'api_token'
    ];

    public function __construct(ActivityLogRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Sistemdə baş verən əməliyyatı qeyd edir
     */
    public function log(
        string $action,
        ?Model $model = null,
        ?array $oldData = null,
        ?array $newData = null,
        array $additionalData = []
    ): void {
        try {
            $logData = array_merge([
                'action' => $action,
                'status' => ActivityLogStatusEnum::SUCCESS,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'method' => request()->method(),
                'url' => request()->fullUrl(),
                'meta_data' => [
                    'browser' => $this->getBrowserInfo(),
                    'platform' => $this->getPlatformInfo(),
                    'device' => $this->getDeviceInfo(),
                ],
            ], $additionalData);

            // Model məlumatlarını əlavə edirik
            if ($model) {
                $logData['model_type'] = get_class($model);
                $logData['model_id'] = $model->id;
            }

            // Dəyişiklik məlumatlarını əlavə edirik
            if ($oldData) {
                $logData['old_data'] = $this->filterSensitiveData($oldData);
            }
            if ($newData) {
                $logData['new_data'] = $this->filterSensitiveData($newData);
            }

            $this->repository->createLog($logData);
        } catch (\Exception $e) {
            // Xəta baş verərsə, xəta logu yazırıq
            $this->logError($action, $e->getMessage(), $model);
        }
    }

    /**
     * Xəta logunu qeyd edir
     */
    public function logError(string $action, string $message, ?Model $model = null): void
    {
        $logData = [
            'action' => $action,
            'status' => ActivityLogStatusEnum::ERROR,
            'error_message' => $message,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'method' => request()->method(),
            'url' => request()->fullUrl(),
        ];

        if ($model) {
            $logData['model_type'] = get_class($model);
            $logData['model_id'] = $model->id;
        }

        $this->repository->createLog($logData);
    }

    /**
     * Köhnə logları təmizləyir
     */
    public function cleanup(int $days = 30): int
    {
        return $this->repository->cleanOldLogs($days);
    }

    /**
     * Həssas məlumatları filtirləyir
     */
    protected function filterSensitiveData(array $data): array
    {
        return array_filter($data, function ($key) {
            return !in_array($key, $this->sensitiveFields);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Browser məlumatlarını əldə edir
     */
    protected function getBrowserInfo(): string
    {
        $agent = request()->userAgent();
        $browser = "Unknown Browser";

        if (preg_match('/MSIE/i', $agent)) $browser = "Internet Explorer";
        elseif (preg_match('/Firefox/i', $agent)) $browser = "Firefox";
        elseif (preg_match('/Chrome/i', $agent)) $browser = "Chrome";
        elseif (preg_match('/Safari/i', $agent)) $browser = "Safari";
        elseif (preg_match('/Opera/i', $agent)) $browser = "Opera";

        return $browser;
    }

    /**
     * Əməliyyat sistemi məlumatlarını əldə edir
     */
    protected function getPlatformInfo(): string
    {
        $agent = request()->userAgent();
        $platform = "Unknown OS";

        if (preg_match('/linux/i', $agent)) $platform = 'Linux';
        elseif (preg_match('/macintosh|mac os x/i', $agent)) $platform = 'Mac';
        elseif (preg_match('/windows|win32/i', $agent)) $platform = 'Windows';

        return $platform;
    }

    /**
     * Cihaz məlumatlarını əldə edir
     */
    protected function getDeviceInfo(): string
    {
        $agent = request()->userAgent();

        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $agent)) {
            return 'tablet';
        }

        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $agent)) {
            return 'mobile';
        }

        return 'desktop';
    }
}
