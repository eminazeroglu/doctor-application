<?php

namespace App\Services\Filter;

class AdvertisementFilter extends BaseFilter
{
    protected array $filters = [
        'position',
        'display_type'
    ];

    protected function filterPosition($query, $value)
    {
        return $query->where('position', $value);
    }

    protected function filterDisplayType($query, $value)
    {
        return $query->where('display_type', $value);
    }
}
