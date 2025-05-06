<?php

namespace App\Services\Filter;

class ActivityLogFilter extends BaseFilter
{
    protected array $filters = [
        'action',
        'status',
        'ip_address',
        'url',
        'method',
    ];

    protected function filterAction($query, $value)
    {
        return $query->where('action', $value);
    }

    protected function filterIpAddress($query, $value)
    {
        return $query->where('ip_address', $value);
    }

    protected function filterUrl($query, $value)
    {
        return $query->where('url', 'like', "%{$value}%");
    }

    protected function filterMethod($query, $value)
    {
        return $query->where('method', $value);
    }
}
