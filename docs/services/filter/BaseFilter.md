# Laravel Dynamic Filter System - Method Documentation
Bu sənəd filter sistemində olan bütün metodları detallı şəkildə izah edir.

## BaseFilter Sinifinin Core Metodları

### `apply()` Metodu
Bu metod bütün filter sisteminin əsas nöqtəsidir. Filterləri query üzərinə tətbiq edir.

```php
public function apply(Builder $query): Builder
{
    // Aktiv filterləri tapır və tətbiq edir
    foreach ($this->getFilters() as $filter => $value) {
        if ($value === null || $value === '') {
            continue;
        }

        $method = 'filter' . Str::studly($filter);
        if (method_exists($this, $method)) {
            $query = $this->$method($query, $value);
        }
    }

    // Sıralama tətbiq edir
    if ($this->request->has('sort')) {
        $query = $this->applySort($query);
    } else {
        $query = $this->applyDefaultSort($query);
    }

    return $query;
}
```

**Necə işləyir?**
1. `getFilters()` metodu ilə aktiv filterləri alır
2. Hər filter üçün müvafiq metodu dinamik olaraq çağırır
3. Boş dəyərləri nəzərə almır
4. Sonda sıralama tətbiq edir

### `filterSearch()` Metodu
Çoxdilli məlumatlar üzərində axtarış aparır.

```php
protected function filterSearch(Builder $query, string $value): Builder
{
    $searchableFields = $this->getSearchableFields();
    $languages = config('app.languages', ['az', 'en', 'ru']);

    return $query->where(function(Builder $q) use ($value, $searchableFields, $languages) {
        foreach ($searchableFields as $field) {
            foreach ($languages as $lang) {
                $q->orWhereRaw(
                    "LOWER(JSON_UNQUOTE(
                        JSON_EXTRACT(translates, '$.{$lang}.{$field}')
                    )) LIKE ?",
                    ['%' . mb_strtolower($value) . '%']
                );
            }
        }
    });
}
```

**Xüsusiyyətləri:**
- Bütün dillərdə eyni zamanda axtarış edir
- Case-insensitive axtarış təmin edir
- JSON_EXTRACT istifadə edərək məlumatları tapır
- SQL injection-dan qorunur

### `applySort()` və `applyDefaultSort()` Metodları
Məlumatların sıralanmasını idarə edir.

```php
protected function applySort(Builder $query): Builder
{
    $sortField = $this->request->get('sort', $this->defaultSortColumn);
    $direction = $this->request->get('direction', $this->defaultSortDirection);
    
    // Təhlükəsizlik yoxlaması
    $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
    
    if (method_exists($this, 'getSortableColumns')) {
        $sortableColumns = $this->getSortableColumns();
        if (!in_array($sortField, $sortableColumns)) {
            $sortField = $this->defaultSortColumn;
        }
    }

    return $query->orderBy($sortField, $direction);
}
```

**Təhlükəsizlik tədbirləri:**
- İcazə verilən sütunları yoxlayır
- Sort istiqamətini validate edir
- Default dəyərlər təyin edir

### Ümumi Filter Metodları

#### `filterStatus()`
Status filtri.
```php
protected function filterStatus(Builder $query, $value): Builder
{
    return $query->where('status', $value);
}
```

#### `filterIsActive()`
Aktivlik filtri.
```php
protected function filterIsActive(Builder $query, $value): Builder
{
    return $query->where('is_active', $value);
}
```

#### `filterDateRange()`
Tarix aralığı filtri.
```php
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
```

#### `filterTrashed()`
Silinmiş məlumatlar filtri.
```php
protected function filterTrashed(Builder $query, string $value): Builder
{
    return match($value) {
        'with' => $query->withTrashed(),
        'only' => $query->onlyTrashed(),
        default => $query
    };
}
```

### Helper Metodları

#### `enableSearchFilter()`
Axtarış filtrini aktivləşdirir.
```php
protected function enableSearchFilter(): void
{
    if (!in_array('search', $this->filters)) {
        $this->filters[] = 'search';
    }
}
```

#### `enableCommonFilters()`
Ümumi filterləri aktivləşdirir.
```php
protected function enableCommonFilters(): void
{
    $commonFilters = ['status', 'is_active', 'date_range', 'trashed'];
    
    foreach ($commonFilters as $filter) {
        if (!in_array($filter, $this->filters)) {
            $this->filters[] = $filter;
        }
    }
}
```

## Nümunələr

### Sadə Filter
```php
class ProductFilter extends BaseFilter
{
    protected array $filters = ['category_id'];
    
    protected function filterCategoryId($query, $value)
    {
        return $query->where('category_id', $value);
    }
}
```

### Kompleks Filter
```php
class OrderFilter extends BaseFilter
{
    protected array $filters = [
        'status',
        'date_range',
        'total_amount',
        'customer_id'
    ];
    
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->enableSearchFilter();
        $this->enableCommonFilters();
    }
    
    protected function getSearchableFields(): array
    {
        return ['order_number', 'customer_name'];
    }
    
    protected function filterTotalAmount($query, array $range)
    {
        return $query->whereBetween('total_amount', [
            $range['min'] ?? 0,
            $range['max'] ?? PHP_FLOAT_MAX
        ]);
    }
}
```

## Performans Tövsiyələri

1. **Eager Loading**
```php
// Lazım olan əlaqələri əvvəlcədən yükləyin
$query->with(['category', 'brand']);
```

2. **İndekslər**
```php
// Tez-tez filter olunan sütunlar üçün indekslər yaradın
$table->index(['status', 'is_active']);
```

3. **Query Optimizasiyası**
```php
// Çox sayda OR şərtindən qaçının
// whereIn() istifadə edin
```

## Təhlükəsizlik Tövsiyələri

1. **SQL Injection**
```php
// Təhlükəli
$query->whereRaw("status = '$status'");

// Təhlükəsiz
$query->where('status', $status);
```

2. **Mass Assignment**
```php
// Filterləri təyin edərkən diqqətli olun
protected array $filters = ['safe_field'];
```

3. **Validation**
```php
// Dəyərləri validate edin
if (!in_array($status, ['active', 'inactive'])) {
    return $query;
}
```

## Test Etmə

### Unit Test Nümunəsi
```php
public function test_filter_applies_search_correctly()
{
    $filter = new ProductFilter(new Request(['search' => 'test']));
    
    $query = Product::query();
    $filteredQuery = $filter->apply($query);
    
    // Assert query contains proper where clauses
    $this->assertStringContainsString(
        'JSON_EXTRACT',
        $filteredQuery->toSql()
    );
}
```

Bu sənəd filter sisteminin bütün aspektlərini əhatə edir. Əlavə suallarınız olarsa və ya hər hansı bir hissəni daha detallı izah etməyimi istəsəniz, bildirə bilərsiniz.
