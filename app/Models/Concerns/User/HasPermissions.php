<?php

namespace App\Models\Concerns\User;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait HasPermissions
{
    /*
    |--------------------------------------------------------------------------
    | PERMISSION METHODS - İcazə metodları
    |--------------------------------------------------------------------------
    */

    /**
     * İstifadəçinin müəyyən icazəyə malik olub-olmadığını yoxlayır
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return $this->getAllPermissions()->contains('name', $permission);
    }

    /**
     * İstifadəçinin bütün icazələrini qaytarır, cache-dən istifadə edir
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        $cacheKey = 'user_permissions_' . $this->id;

        return Cache::remember($cacheKey, now()->addHours(24), function () {
            return $this->role->permissions;
        });
    }

    /**
     * İstifadəçinin icazə cache-ini təmizləyir
     *
     * @return void
     */
    public function forgetCachedPermissions(): void
    {
        Cache::forget('user_permissions_' . $this->id);
    }

    /**
     * İstifadəçinin müəyyən rolda olub-olmadığını yoxlayır
     *
     * @param string $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role->group_name === $roleName;
    }
}
