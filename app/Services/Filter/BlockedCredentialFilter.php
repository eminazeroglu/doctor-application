<?php

namespace App\Services\Filter;

class BlockedCredentialFilter extends BaseFilter
{
    protected array $filters = [
        'type',
        'value',
        'reason',
    ];

    protected function filterType($query, $value)
    {
        return $query->where('type', $value);
    }

    protected function filterValue($query, $value)
    {
        return $query->where('value', 'like', "%$value%");
    }

    protected function filterReason($query, $value)
    {
        return $query->where('reason', 'like', "%$value%");
    }
}
