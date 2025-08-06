<?php

namespace App\Services\Filter;

class BlogFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'category_id',
    ];

    protected function getSearchableFields(): array
    {
        return [
            'title',
            'description',
            'content',
        ];
    }

    public function filterCategoryId($query, $value)
    {
        return $query->where('category_id', $value);
    }
}
