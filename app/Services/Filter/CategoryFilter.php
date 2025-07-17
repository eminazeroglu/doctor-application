<?php

namespace App\Services\Filter;

class CategoryFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'parent_id',
        'is_home',
    ];

    protected function filterParentId($query, $value)
    {
        return $query->where('parent_id', $value);
    }

    protected function filterIsHome($query, $value)
    {
        return $query->where('is_home', $value === 1);
    }

    protected function getSearchableFields(): array
    {
        return ['name'];
    }
}
