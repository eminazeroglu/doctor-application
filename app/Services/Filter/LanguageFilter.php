<?php

namespace App\Services\Filter;

class LanguageFilter extends BaseFilter
{
    protected array $filters = [
        'name'
    ];

    protected function filterName($query, $value)
    {
        return $query->where('name', 'like', '%' . $value . '%');
    }
}
