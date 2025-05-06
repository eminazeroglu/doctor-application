<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;

class PageFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'status',
        'type',
        'page_id',
        'trashed'
    ];

    protected function getSearchableFields(): array
    {
        return ['name', 'content', 'title', 'description']; // Həm Page, həm də PageWidget üçün
    }

    protected function filterType(Builder $query, $value): Builder
    {
        return $query->where('type', $value);
    }

    protected function filterPageId(Builder $query, $value): Builder
    {
        return $query->where('page_id', $value);
    }

    // Bu metod PageWidget modelinə aid olduğu üçün, model tipini yoxlamalıyıq
    protected function filterSearch(Builder $query, string $value): Builder
    {
        $modelClass = get_class($query->getModel());

        if ($modelClass === \App\Models\Page::class) {
            // Page modeli üçün axtarış
            return parent::filterSearch($query, $value);
        } else {
            // PageWidget modeli üçün axtarış
            return $query->where(function ($q) use ($value, $query) {
                foreach ($this->getSearchableFields() as $field) {
                    if (in_array($field, $query->getModel()->getTranslatableAttributes())) {
                        $q->orWhereRaw("JSON_CONTAINS(translates, ?, '$.\$.{$field}')", ["%{$value}%"]);
                    } else {
                        $q->orWhere($field, 'LIKE', "%{$value}%");
                    }
                }
            });
        }
    }
}
