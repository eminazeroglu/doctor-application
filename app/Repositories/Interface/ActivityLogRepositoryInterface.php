<?php

namespace App\Repositories\Interface;

use App\Models\ActivityLog;

interface ActivityLogRepositoryInterface extends BaseRepositoryInterface
{

    /**
     * Logu yaradır və xətaları idarə edir
     */
    public function createLog(array $data): ActivityLog;

    /**
     * Müəyyən vaxtdan əvvəlki logları təmizləyir
     */
    public function cleanOldLogs(int $days = 30): int;
}
