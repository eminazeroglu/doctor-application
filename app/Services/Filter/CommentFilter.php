<?php

namespace App\Services\Filter;

class CommentFilter extends BaseFilter
{
    protected array $filters = [
        'type',
        'author',
    ];

    protected function filterType($query, $value)
    {
        return $query->where('commentable_type', $value);
    }

    protected function filterAuthor($query, $value)
    {
        return $query->whereHas('author', function ($query) use ($value) {
            $query->fullName($value);
        });
    }
}
