<?php

namespace Database\Seeders;

use App\Services\Module\PermissionService;
use Exception;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        $this->permissionService->syncPermissions();
        $this->permissionService->syncRoles();
    }
}
