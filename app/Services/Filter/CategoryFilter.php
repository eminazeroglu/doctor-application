<?php

namespace App\Services\Filter;

class CategoryFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'parent_id',
    ];

    protected function filterParentId($query, $value)
    {
        return $query->where('parent_id', $value);
    }

    protected function getSearchableFields(): array
    {
        return ['name'];
    }
}
