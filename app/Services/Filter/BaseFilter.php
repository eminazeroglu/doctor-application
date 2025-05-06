<?php

namespace App\Services\Filter;

use App\Contracts\FilterInterface;
use App\Models\Language;
use App\Services\Module\TranslationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class BaseFilter implements FilterInterface
{
    /**
     * Request instance
     */
    protected Request $request;

    /**
     * Aktiv filterlərin siyahısı
     * Child class-lar bu array-i doldurmalıdır
     */
    protected array $filters = [];

    /**
     * Default sort column
     */
    protected string $defaultSortColumn = 'id';

    /**
     * Default sort direction
     */
    protected string $defaultSortDirection = 'desc';

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * FilterInterface-dən gələn metod
     * Bütün filterləri tətbiq edir
     */
    public function apply(Builder $query): Builder
    {
        // Bütün aktiv filterləri tapıb tətbiq edirik
        foreach ($this->getFilters() as $filter => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // filter_name -> filterName çeviririk
            $method = 'filter' . Str::studly($filter);

            // Əgər filter metodu mövcuddursa, çağırırıq
            if (method_exists($this, $method)) {
                $query = $this->$method($query, $value);
            }
        }

        // Sort əgər request-də varsa
        if ($this->request->has('sort')) {
            $query = $this->applySort($query);
        } else {
            $query = $this->applyDefaultSort($query);
        }

        return $query;
    }

    /**
     * Request-dən yalnız təyin olunmuş filterləri alırıq
     */
    protected function getFilters(): array
    {
        return array_filter($this->request->only($this->filters));
    }

    /**
     * Default sıralama tətbiq edir
     */
    protected function applyDefaultSort(Builder $query): Builder
    {
        return $query->orderBy($this->defaultSortColumn, $this->defaultSortDirection);
    }

    /**
     * Request-dən gələn sıralama parametrlərini tətbiq edir
     */
    protected function applySort(Builder $query): Builder
    {
        $sortField = $this->request->get('sort', $this->defaultSortColumn);
        $direction = $this->request->get('direction', $this->defaultSortDirection);

        // Təhlükəsizlik yoxlaması - direction yalnız asc və ya desc ola bilər
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        // Əgər sortable columns təyin olunubsa, yoxlayırıq
        if (method_exists($this, 'getSortableColumns')) {
            $sortableColumns = $this->getSortableColumns();
            if (!in_array($sortField, $sortableColumns)) {
                $sortField = $this->defaultSortColumn;
            }
        }

        if (isset($query->getModel()->getCasts()['translates']) && in_array($sortField, $query->getModel()->getTranslatableAttributes())) {
            $lang = currentLang();
            $sortField = "translates->{$lang}->{$sortField}";
        }

        if (!in_array($this->request->get('sort'), array_merge($query->getModel()->getFillable(), ['id']))) {
            $sortField = $this->defaultSortColumn;
        }

        return $query->orderBy($sortField, $direction);
    }

    /**
     * Çoxdilli kontentdə axtarış aparmaq üçün əsas metod
     */
    protected function filterSearch(Builder $query, string $value): Builder
    {
        // Axtarış ediləcək sahələri əldə edirik
        $searchableFields = $this->getSearchableFields();

        // Modelin bütün sütunlarını əldə edirik
        $tableColumns = $query->getModel()->getFillable();

        // Sistemdə mövcud olan dilləri əldə edirik
        $languages = app(TranslationService::class)->getActiveLanguages()->pluck('locale')->toArray();

        return $query->where(function (Builder $q) use ($value, $searchableFields, $languages, $tableColumns) {
            // Translates sütununda axtarış
            foreach ($searchableFields as $field) {
                // Əgər bu sahə normal sütunlarda varsa, onda orda da axtarış edirik
                if (in_array($field, $tableColumns)) {
                    $q->orWhere($field, 'like', '%' . mb_strtolower($value) . '%');
                }

                if (in_array('translates', $tableColumns)) {
                    // Translates-də axtarış
                    foreach ($languages as $lang) {
                        $q->orWhereRaw(
                            "LOWER(JSON_UNQUOTE(
                                JSON_EXTRACT(translates, '$.{$lang}.{$field}')
                            )) LIKE ?",
                            ['%' . mb_strtolower($value) . '%']
                        );
                    }
                }
            }
        });
    }

    /**
     * Status filtri üçün ümumi metod
     */
    protected function filterStatus(Builder $query, $value): Builder
    {
        return $query->where('status', $value);
    }

    /**
     * Aktivlik filtri üçün ümumi metod
     */
    protected function filterIsActive(Builder $query, $value): Builder
    {
        return $query->where('is_active', $value);
    }

    /**
     * Tarix aralığı filtri üçün ümumi metod
     */
    protected function filterDateRange(Builder $query, array $value): Builder
    {
        if (isset($value['from'])) {
            $query->whereDate('created_at', '>=', $value['from']);
        }

        if (isset($value['to'])) {
            $query->whereDate('created_at', '<=', $value['to']);
        }

        return $query;
    }

    /**
     * Silinmiş dataların filtri üçün ümumi metod
     */
    protected function filterTrashed(Builder $query, string $value): Builder
    {
        return match ($value) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => $query
        };
    }

    /**
     * Axtarış ediləcək sahələri təyin edən abstract metod
     * Hər bir model özünə aid axtarış sahələrini təyin etməlidir
     */
    protected function getSearchableFields(): array
    {
        return []; // Varsayılan olaraq heç bir sahə yoxdur.
    }

    /**
     * Filterin axtarış funksionallığını aktivləşdirmək üçün köməkçi metod
     */
    protected function enableSearchFilter(): void
    {
        if (!in_array('search', $this->filters)) {
            $this->filters[] = 'search';
        }
    }

    /**
     * Ümumi filterləri aktivləşdirmək üçün köməkçi metod
     */
    protected function enableCommonFilters(): void
    {
        $commonFilters = ['status', 'is_active', 'date_range', 'trashed'];

        foreach ($commonFilters as $filter) {
            if (!in_array($filter, $this->filters)) {
                $this->filters[] = $filter;
            }
        }
    }
}
