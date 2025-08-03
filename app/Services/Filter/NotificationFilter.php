<?php

namespace App\Services\Filter;

class NotificationFilter extends BaseFilter
{
    protected array $filters = [
        'name'
    ];

    protected function filterName($query, $value)
    {
        return $query->where('name', $value);
    }
}
