<?php

namespace App\Services\Filter;

class TestimonialFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'fullname',
        'comment',
    ];

    protected function filterFullname($query, $value)
    {
        return $query->translationSearchInLanguage($value, 'fullname');
    }

    protected function filterComment($query, $value)
    {
        return $query->translationSearchInLanguage($value, 'comment');
    }
}
