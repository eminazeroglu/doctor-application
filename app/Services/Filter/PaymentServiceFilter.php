<?php

namespace App\Services\Filter;

class PaymentServiceFilter extends BaseFilter
{
    protected array $filters = [
        'key_name',
        'type',
        'option_type',
    ];

    protected function filterType($query, $value)
    {
        return $query->where('type', $value);
    }

    protected function filterKeyName($query, $value)
    {
        return $query->where('key_name', $value);
    }

    protected function filterOptionType($query, $value)
    {
        return $query->where('option_type', $value);
    }
}
