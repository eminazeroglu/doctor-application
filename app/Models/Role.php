<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'guard_name'];

    protected $hidden = ['created_at', 'updated_at', 'guard_name'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function syncPermissions($permissions): array
    {
        $result = $this->permissions()->sync($permissions);
        $this->clearUsersPermissionCache();
        return $result;
    }

    public function givePermissionTo($permission): array
    {
        $result = $this->permissions()->syncWithoutDetaching($permission);
        $this->clearUsersPermissionCache();
        return $result;
    }


    public function revokePermissionTo($permission): int
    {
        $result = $this->permissions()->detach($permission);
        $this->clearUsersPermissionCache();
        return $result;
    }

    protected function clearUsersPermissionCache(): void
    {
        $this->users->each(function ($user) {
            $user->forgetCachedPermissions();
        });
    }

    public function hasPermissionTo($permission)
    {
        return $this->permissions->contains('name', $permission);
    }
}
