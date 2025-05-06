<?php

namespace App\Repositories;

use App\Contracts\FilterInterface;
use App\Repositories\Interface\BaseRepositoryInterface;
use App\Repositories\Traits\{HasCache, HasFilters, HasQueryBuilder};
use App\Traits\Model\HasImage;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

abstract class BaseRepository implements BaseRepositoryInterface
{
    use HasCache, HasFilters, HasQueryBuilder;

    public Model $model;
    protected ?FilterInterface $filter = null;
    protected string $configKey = 'default';
    protected array $with = [];
    protected array $withCount = [];
    protected bool $useCache = false;
    protected int $cacheTtl = 3600;

    // Cədvəl sütunları üçün cache və müvəqqəti əlaqələr
    protected static array $columnsCache = [];
    private array $tableColumns = [];
    protected array $tempWith = [];

    public function __construct(Model $model, $useCache = false)
    {
        $this->model = $model;
        $this->useCache = $useCache;
        $this->loadTableColumns();
    }

    protected function loadTableColumns(): void
    {
        $tableName = $this->model->getTable();

        if (!isset(static::$columnsCache[$tableName])) {
            $cacheKey = "table.columns.{$tableName}";

            static::$columnsCache[$tableName] = Cache::remember($cacheKey, now()->addWeek(), function() use($tableName) {
                $dbName = config('database.connections.' . config('database.default') . '.database');

                try {
                    return DB::table('information_schema.columns')
                        ->select('column_name')
                        ->where('table_schema', $dbName)
                        ->where('table_name', $tableName)
                        ->pluck('column_name')
                        ->toArray();
                } catch (\Exception $e) {
                    Log::warning("Column fetch failed for {$tableName}, using Schema::getColumnListing()");
                    return Schema::getColumnListing($tableName);
                }
            });
        }

        $this->tableColumns = static::$columnsCache[$tableName];
    }

    public function setUseCache(bool $useCache): BaseRepositoryInterface
    {
        $this->useCache = $useCache;
        return $this;
    }

    public function setFilter(FilterInterface $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    public function with(array $relations): self
    {
        $this->tempWith = array_merge($this->with, $relations);
        return $this;
    }

    public function withCount(array $relations): self
    {
        $this->withCount = $relations;
        return $this;
    }

    protected function applyRelations($query): void
    {
        if (!empty($this->with)) {
            $query->with($this->with);
        }

        if (!empty($this->tempWith)) {
            $query->with($this->tempWith);
            $this->tempWith = [];
        }

        if (!empty($this->withCount)) {
            $query->withCount($this->withCount);
        }
    }

    protected function afterCreate($model): void {}
    protected function afterUpdate($model): void {}
    protected function afterDelete($model): void {}
    protected function afterStatusChange($model): void {}
    protected function beforeCreate(array &$data): void {}
    protected function beforeUpdate(Model $model, array &$data): void {}
    protected function beforeDelete(Model $model): void {}
    protected function beforeStatusChange(Model $model, string $statusField): void {}

    protected static function bootBaseRepository(): void
    {
        if (app()->runningInConsole()) {
            static::clearColumnsCache();
        }
    }

    public function uploadImageControl($data)
    {
        $model = $this->model;
        if (in_array(HasImage::class, class_uses_recursive($model)) && count($model?->getImageFields()) > 0) {
            foreach ($model->getImageFields() as $field => $value) {
                if (!optional($data)[$field]) {
                    unset($data[$field]);
                }
            }
        }
        return $data;
    }

    /**
     * Metodun məqsədi: Verilən callback-i tranzaksiya daxilində icra edir.
     * Tranzaksiya uğurlu olarsa nəticəni qaytarır, uğursuz olarsa rollback edir.
     *
     * @param callable $callback Tranzaksiya daxilində icra olunacaq funksiya
     * @return mixed Callback-in qaytardığı nəticə
     * @throws Exception Xəta baş verərsə yenidən atılır
     */
    protected function transact(callable $callback): mixed
    {
        try {
            DB::beginTransaction();
            Schema::disableForeignKeyConstraints();

            $result = $callback();

            Schema::enableForeignKeyConstraints();
            DB::commit();

            if ($this->useCache) {
                $this->clearCache();
            }

            return $result;
        } catch (Exception $e) {
            Schema::enableForeignKeyConstraints();
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function create(array $data)
    {
        $cacheKey = $this->getCacheKey('create', $data);

        return $this->remember($cacheKey, function () use ($data) {
            return $this->transact(function () use ($data) {
                $this->beforeCreate($data);

                if ($this->tableHasColumn('parent_id') && empty($data['parent_id'])) {
                    $data['parent_id'] = 0;
                }

                $data = $this->uploadImageControl($data);
                $model = $this->model->query()->create($data);

                $this->applyRelations($model);
                $this->afterCreate($model);

                return $model;
            });
        });
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data): Model
    {
        return $this->transact(function () use ($id, $data) {
            $model = $this->findById($id);

            $this->beforeUpdate($model, $data);

            $data = $this->uploadImageControl($data);
            $model->update($data);

            $this->applyRelations($model);
            $this->afterUpdate($model);

            return $model;
        });
    }

    /**
     * @throws Exception
     */
    public function changeStatus(int $id, $statusField = 'is_active'): Model
    {
        return $this->transact(function () use ($id, $statusField) {
            $model = $this->findById($id);

            $this->beforeStatusChange($model, $statusField);

            if ($statusField === 'is_default' && $this->tableHasColumn('is_default')) {
                $this->model->update(['is_default' => false]);
            }

            $model->$statusField = !$model->$statusField;
            $model->save();

            $this->afterStatusChange($model);
            $this->applyRelations($model);

            return $model;
        });
    }

    /**
     * @throws Exception
     */
    public function delete(int $id): mixed
    {
        return $this->transact(function () use ($id) {
            $model = $this->findById($id);

            $this->beforeDelete($model);

            if ($this->tableHasColumn('is_system')) {
                $result = $this->model->query()
                    ->where(['id' => $id, 'is_system' => false])
                    ->delete();
            } else {
                $result = $model->delete();
            }

            if ($result) {
                $this->afterDelete($model);
            }

            return $result;
        });
    }

    public function findOneWhere(array $conditions): ?Model
    {
        $cacheKey = $this->getCacheKey('findOneWhere', $conditions);

        return $this->remember($cacheKey, function () use ($conditions) {
            $query = $this->model->query();
            $this->applyRelations($query);
            return $query->where($conditions)->first();
        });
    }

    /**
     * @param array $orders
     * @return bool
     */
    public function updateOrder(array $orders): bool
    {
        // Data validasiyası
        foreach ($orders as $order) {
            if (!isset($order['id']) || !array_key_exists('order', $order)) {
                throw new \InvalidArgumentException(
                    'Invalid order data structure. Each item must contain id and order fields.'
                );
            }
        }

        try {
            DB::beginTransaction();

            // Eyni order dəyərinə sahib elementləri qruplaşdırırıq
            $groupedOrders = collect($orders)->groupBy('order');

            foreach ($groupedOrders as $orderValue => $items) {
                // Elementləri ID-yə görə sıralayırıq
                $sortedItems = $items->sortBy('id');

                // Hər bir elementi yeniləyirik
                $sortedItems->values()->each(function ($item, $index) use ($orderValue) {
                    $this->model->query()->findOrFail($item['id'])->update(
                        ['order' => (int)$orderValue + $index]
                    );
                });
            }

            DB::commit();

            if ($this->useCache) {
                $this->clearCache();
            }

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            report($e);
        }
    }

    public function findWhere(array $conditions): Collection
    {
        $cacheKey = $this->getCacheKey('findWhere', $conditions);

        return $this->remember($cacheKey, function () use ($conditions) {
            $query = $this->model->query();
            $this->applyRelations($query);
            return $query->where($conditions)->get();
        });
    }

    public function findWhereIn(array $conditions): Collection
    {
        $cacheKey = $this->getCacheKey('findWhereIn', $conditions);

        return $this->remember($cacheKey, function () use ($conditions) {
            $query = $this->model->query();
            $this->applyRelations($query);

            foreach ($conditions as $column => $values) {
                $query->whereIn($column, $values);
            }

            return $query->get();
        });
    }

    public function updateWhere(array $conditions, array $data): bool
    {
        $result = $this->model->query()->where($conditions)->update($data);

        if ($this->useCache) {
            $this->clearCache();
        }

        return (bool)$result;
    }

    public function deleteWhere(array $conditions): bool
    {
        $result = $this->model->query()->where($conditions)->delete();

        if ($this->useCache) {
            $this->clearCache();
        }

        return (bool)$result;
    }

    public function findById(int $id): Model
    {
        $cacheKey = $this->getCacheKey('findById', [
            'id' => $id,
            'relations' => array_merge($this->with, $this->tempWith)
        ]);

        return $this->remember($cacheKey, function () use ($id) {
            $query = $this->model->query();
            $this->applyRelations($query);
            return $query->findOrFail($id);
        });
    }

    public function findByUuid(string $uuid): Model
    {
        $cacheKey = $this->getCacheKey('findByUuid', [
            'uuid' => $uuid,
            'relations' => array_merge($this->with, $this->tempWith)
        ]);

        return $this->remember($cacheKey, function () use ($uuid) {
            $query = $this->model->query();
            $this->applyRelations($query);
            return $query->where('uuid', $uuid)->firstOrFail();
        });
    }

    public function findAll(): Collection
    {
        $cacheKey = $this->getCacheKey('findAll');

        return $this->remember($cacheKey, function () {
            $query = $this->model->query();
            $this->applyRelations($query);
            return $query->get();
        });
    }

    public function findActiveList(): Collection
    {
        $cacheKey = $this->getCacheKey('findActiveList');

        return $this->remember($cacheKey, function () {
            $query = $this->model->query();

            if ($this->tableHasColumn('is_active')) {
                $query->where('is_active', true);
            }

            $this->applyRelations($query);
            return $query->get();
        });
    }

    public function findBySlug(string $slug, ?string $locale = null): Model
    {
        $query = $this->model->newQuery();
        $this->applyRelations($query);

        if ($locale && method_exists($this->model, 'getTranslatableAttributes')) {
            return $query->where(function ($q) use ($slug, $locale) {
                $q->where('slug', $slug)
                    ->orWhereRaw(
                        "JSON_UNQUOTE(JSON_EXTRACT(translates, '$.{$locale}.slug')) = ?",
                        [$slug]
                    );
            })->firstOrFail();
        }

        return $query->where('slug', $slug)->firstOrFail();
    }

    public function findByLocalizedSlug(string $slug, string $locale): Model
    {
        return $this->findBySlug($slug, $locale);
    }

    public function paginateAndFilter(): LengthAwarePaginator|Collection
    {
        $cacheKey = $this->getCacheKey('paginateAndFilter', request()->all());

        return $this->remember($cacheKey, function () {
            $query = $this->model->newQuery();
            $this->applyRelations($query);

            if ((request()->has('tree') && $this->tableHasColumn('parent_id')) || request()->has('full')) {
                if ($this->tableHasColumn('parent_id')) {
                    $query->with('children');
                    $query = $this->handleTreeStructure($query);
                }
                return $query->get();
            }

            if ($this->filter) {
                $query = $this->filter->apply($query);
            }

            return $this->paginate($query);
        });
    }

    protected function handleTreeStructure(Builder $query): Builder
    {
        $resultIds = null;

        if (request()->has('search')) {
            $resultIds = $this->getTreeSearchResults($query);

            $query->where('parent_id', 0)
                ->where(function($q) use ($resultIds) {
                    $q->whereIn('id', $resultIds);
                });
        } else {
            if ($this->filter) {
                $query = $this->filter->apply($query);
            }
            $query->where('parent_id', 0);
        }

        $query->with(['children' => function ($query) use ($resultIds) {
            $this->loadTreeChildrenRecursively($query, $resultIds);
        }]);

        return $query;
    }

    protected function getTreeSearchResults(Builder $query): \Illuminate\Support\Collection
    {
        $searchQuery = $this->model->newQuery();

        if ($this->filter) {
            $searchQuery = $this->filter->apply($searchQuery);
        }

        $resultIds = collect();

        $searchQuery->chunk(100, function($items) use (&$resultIds) {
            foreach ($items as $item) {
                $resultIds->push($item->id);

                $parent = $item;
                while ($parent->parent_id != 0) {
                    $resultIds->push($parent->parent_id);
                    $parent = $parent->parent;
                }
            }
        });

        return $resultIds->unique();
    }

    protected function loadTreeChildrenRecursively($query, $resultIds = null): void
    {
        $query->with(['children' => function ($q) use ($resultIds) {
            $this->loadTreeChildrenRecursively($q, $resultIds);
        }]);

        if ($resultIds) {
            $query->where(function($q) use ($resultIds) {
                $q->whereIn('id', $resultIds);
            });
        }

        if (request()->has('is_active')) {
            $query->where('is_active', request()->get('is_active'));
        }

        if ($this->tableHasColumn('order')) {
            $query->orderBy('order', 'asc');
        }
    }

    public function filters()
    {
        return [];
    }

    public function bulk($type, $request)
    {
        return [
            'type' => $type,
            ...$request->all()
        ];
    }

    protected function paginate(Builder $query): LengthAwarePaginator
    {
        $sortField = request()->get('orderColumn', 'id');
        $direction = request()->get('orderDirection', 'desc');
        $limit = request()->get('limit', 25);

        $query->orderBy($sortField, $direction);

        return $query->paginate($limit)->withQueryString();
    }

    public function tableHasColumn(string $column): bool
    {
        return in_array($column, $this->tableColumns);
    }

    protected function getCacheKey(string $key, array $params = []): string
    {
        $baseKey = sprintf('%s_%s', $this->model->getTable(), $key);

        if (!empty($this->with) || !empty($this->tempWith)) {
            $params['relations'] = implode(',', array_merge($this->with, $this->tempWith));
        }

        if (!empty($params)) {
            ksort($params);
            $paramsKey = md5(json_encode($params));
            $baseKey .= '_' . $paramsKey;
        }

        return $baseKey;
    }

    public static function clearColumnsCache(): void
    {
        foreach (static::$columnsCache as $tableName => $columns) {
            Cache::forget("table.columns.{$tableName}");
        }
        static::$columnsCache = [];
    }
}
