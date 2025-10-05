<?php

namespace App\Services\Filter;

class ServiceFilter extends BaseFilter
{
    protected array $filters = [
        'name'
    ];

    protected function filterName($query, $value)
    {
        return $query->translationSearchInLanguage($value, 'name');
    }
}
