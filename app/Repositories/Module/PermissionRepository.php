<?php

namespace App\Repositories\Module;

use App\Models\Permission;
use App\Models\Role;
use App\Repositories\BaseRepository;
use App\Services\Filter\PermissionFilter;
use Illuminate\Support\Facades\DB;

class PermissionRepository extends BaseRepository
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
        $this->setFilter(new PermissionFilter(request()));
    }

    public function delete(int $id): bool
    {
        $model = $this->model->query()->where(['id' => $id, 'is_system' => 0]);
        $result = $model->delete();

        if ($result) {
            if ($this->useCache) {
                $this->clearCache();
            }
        }

        return $result;
    }

    public function getGroupedPermissions(): array
    {
        $permissions = Permission::all()->pluck('name')->toArray();
        $groupedPermissions = [];

        foreach ($permissions as $permission) {
            $parts = explode('_', $permission);

            if (count($parts) >= 2) {
                // Son hissəni action kimi götürürük
                $action = array_pop($parts);
                // Qalan hissələri group kimi birləşdiririk
                $group = implode('_', $parts);

                $groupedPermissions[$group][] = $action;
            }
        }

        foreach ($groupedPermissions as &$actions) {
            sort($actions);
        }
        ksort($groupedPermissions);

        return $groupedPermissions;
    }

    public function getPermissionComparison($roleId): array
    {
        $allPermissions = $this->getGroupedPermissions();
        $role = Role::with('permissions')->findOrFail($roleId);
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        $comparison = [];

        foreach ($allPermissions as $group => $actions) {
            $comparison[$group] = [
                'name' => t("enums.permissions.groups.{$group}", null, str($group)->title()),
                'permissions' => []
            ];

            foreach ($actions as $action) {
                $permissionName = "{$group}_{$action}";
                $comparison[$group]['permissions'][$action] = [
                    'name' => t("enums.permissions.actions.{$action}", null, str($action)->title()),
                    'value' => in_array($permissionName, $rolePermissions)
                ];
            }
        }

        return [
            'role' => $role->name,
            'permissions' => $comparison
        ];
    }

    /**
     * @throws \Exception
     */
    public function updatePermissions($roleId, $permissions): bool
    {
        try {
            DB::beginTransaction();

            $role = Role::findOrFail($roleId);
            $permissionsToSync = [];

            foreach ($permissions['permissions'] as $group => $data) {
                foreach ($data['permissions'] as $action => $permission) {
                    if ($permission['value']) {
                        $permissionName = "{$group}_{$action}";
                        $permissionModel = Permission::firstOrCreate(['name' => $permissionName]);
                        $permissionsToSync[] = $permissionModel->id;
                    }
                }
            }

            $role->permissions()->sync($permissionsToSync);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // Add any additional methods here
}
