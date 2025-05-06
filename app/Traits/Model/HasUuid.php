<?php

namespace App\Traits\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot function from Laravel.
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Scope a query to only include the given uuid.
     *
     * @param Builder $query
     * @param string $uuid
     * @return Builder
     */
    public function scopeWhereUuid(Builder $query, string $uuid): Builder
    {
        return $query->where('uuid', $uuid);
    }

    /**
     * Find a model by its uuid.
     *
     * @param string $uuid
     * @param array $columns
     * @return Model|Collection|HasUuid|null
     */
    public static function findByUuid(string $uuid, array $columns = ['*']): Model|Collection|null|static
    {
        return static::whereUuid($uuid)->first($columns);
    }
}
