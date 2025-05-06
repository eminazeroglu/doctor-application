<?php

namespace App\Services\Module;

use App\Models\Permission;
use App\Models\Role;
use App\Repositories\Module\PermissionRepository;
use App\Services\BaseCrudService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class PermissionService extends BaseCrudService
{
    protected mixed $config;

    public function __construct(PermissionRepository $repository)
    {
        parent::__construct($repository);
        $this->config = Config::get('permissions');
    }

    public function fetchGroupedPermissions($id)
    {
        return $this->repository->getPermissionComparison($id);
    }

    public function updatePermissions($id, $request)
    {
        return $this->repository->updatePermissions($id, $request);
    }

    /**
     * @throws \Exception
     */
    public function syncPermissions(): void
    {
        DB::beginTransaction();
        try {
            // İndi permissions birbaşa permissions array-indən gəlir
            $permissions = $this->config['permissions'];

            foreach ($permissions as $group => $groupPermissions) {
                foreach ($groupPermissions as $permission) {
                    Permission::firstOrCreate(
                        ['name' => $permission],
                    );
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function syncRoles(): void
    {
        DB::beginTransaction();
        try {
            foreach ($this->config['roles'] as $roleName => $rolePermissions) {
                $role = Role::firstOrCreate([
                    'name' => str($roleName)->title(),
                    'group_name' => $roleName,
                    'is_system' => true
                ]);

                if ($roleName === 'admin') {
                    // Admin üçün bütün permissions array-ini istifadə edirik
                    $allPermissions = collect($this->config['permissions'])
                        ->flatten()
                        ->unique()
                        ->values()
                        ->toArray();

                    $permissionIds = Permission::whereIn('name', $allPermissions)
                        ->pluck('id')
                        ->toArray();
                } else {
                    // Digər rollar üçün verilmiş icazələri istifadə edirik
                    $permissionIds = Permission::whereIn('name', $rolePermissions)
                        ->pluck('id')
                        ->toArray();
                }

                $role->syncPermissions($permissionIds);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getAllPermissions()
    {
        return $this->config['permissions'];
    }

    public function getRolePermissions($roleName)
    {
        if ($roleName === 'admin') {
            return $this->getAllPermissions();
        }
        return $this->config['roles'][$roleName] ?? [];
    }

    public function hasPermission($user, $permission)
    {
        return $user->role->permissions->contains('name', $permission);
    }
}
