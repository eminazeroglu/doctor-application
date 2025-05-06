<?php

namespace App\Traits\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait HasCode
{
    /**
     * Boot the trait.
     */
    protected static function bootHasCode(): void
    {
        static::creating(function (Model $model) {
            $codeField = $model->getCodeFieldName();
            if (!$model->{$codeField}) {
                $model->{$codeField} = $model->generateUniqueCode();
            }
        });

        static::saving(function (Model $model) {
            $codeField = $model->getCodeFieldName();
            if (!$model->{$codeField}) {
                $model->{$codeField} = $model->generateUniqueCode();
            }
        });
    }

    /**
     * Get the name of the code field for this model.
     *
     * @return string
     */
    public function getCodeFieldName(): string
    {
        return $this->codeField ?? 'code';
    }

    /**
     * Generate a unique code.
     *
     * @return string
     */
    public function generateUniqueCode(): string
    {
        $length = $this->getCodeLength();

        do {
            $code = $this->generateCode($length);
        } while ($this->codeExists($code));

        return $code;
    }

    /**
     * Generate a code based on specified length.
     *
     * @param int $length
     * @return string
     */
    protected function generateCode(int $length): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        $charactersLength = strlen($characters);

        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, $charactersLength - 1)];
        }

        return $code;
    }

    /**
     * Check if a code already exists.
     *
     * @param string $code
     * @return bool
     */
    protected function codeExists(string $code): bool
    {
        return static::where($this->getCodeFieldName(), $code)->exists();
    }

    /**
     * Get the length of the code.
     *
     * @return int
     */
    protected function getCodeLength(): int
    {
        return $this->codeLength ?? 7;
    }

    /**
     * Scope a query to find by code.
     *
     * @param Builder $query
     * @param string $code
     * @return Builder
     */
    public function scopeWhereCode(Builder $query, string $code): Builder
    {
        return $query->where($this->getCodeFieldName(), $code);
    }

    /**
     * Find a model by its code.
     *
     * @param string $code
     * @param array $columns
     * @return Model|Collection|HasCode|null
     */
    public static function findByCode(string $code, array $columns = ['*']): Model|Collection|null|static
    {
        $instance = new static;
        return $instance->whereCode($code)->first($columns);
    }
}
