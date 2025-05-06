<?php

namespace App\Services\Filter;

class CountryFilter extends BaseFilter
{
    protected array $filters = [
        'search'
    ];

    protected function getSearchableFields(): array
    {
        return [
            'name'
        ];
    }
}
