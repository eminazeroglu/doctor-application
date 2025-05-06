<?php

namespace App\Services\Filter;

class TranslationFilter extends BaseFilter
{
    protected array $filters = [
        'key'
    ];

    protected function filterKey($query, $value)
    {
        return $query->where('key', 'like', "%{$value}%");
    }
}
