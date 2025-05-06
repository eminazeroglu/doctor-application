<?php

namespace App\Services\Filter;

class CityFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'country_id',
    ];

    protected function filterCountryId($query, $value)
    {
        return $query->where('country_id', $value);
    }

    protected function getSearchableFields(): array
    {
        return [
            'name',
        ];
    }
}
