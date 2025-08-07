<?php

namespace App\Services\Filter;

class FaqFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'category_id',
    ];

    protected function getSearchableFields(): array
    {
        return [
            'question',
            'answer',
        ];
    }
}
