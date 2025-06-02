<?php

namespace App\Services\Filter;

class AttributeFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'parent_id',
        'type',
        'position',
    ];

    protected function filterParentId($query, $value)
    {
        return $query->where('parent_id', $value);
    }

    protected function filterType($query, $value)
    {
        return $query->where('type', $value);
    }

    protected function filterPosition($query, $value)
    {
        return $query->where('position', $value);
    }

    protected function getSearchableFields(): array
    {
        return ['name'];
    }
}
