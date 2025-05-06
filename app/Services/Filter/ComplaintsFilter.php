<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;

class ComplaintsFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'status',
        'user',
        'type',
    ];

    protected function filterName($query, $value)
    {
        return $query->where('name', $value);
    }

    /**
     * Metodun məqsədi: Şikayətləri istifadəçi ID-si ilə filtrləyir.
     * Müəyyən bir istifadəçinin şikayətlərini tapır.
     */
    protected function filterUser(Builder $query, $value): Builder
    {
        return $query->whereHas('user', function (Builder $query) use ($value) {
            $query->fullName($value);
        });
    }

    /**
     * Metodun məqsədi: Şikayətləri obyekt tipinə görə filtrləyir.
     * User, company və ya listing tipli şikayətləri ayırır.
     */
    protected function filterType(Builder $query, $value): Builder
    {
        return $query->where('complaintable_type', $value);
    }

    public function getSearchableFields(): array
    {
        return [
            'code'
        ];
    }
}
