<?php

namespace App\Repositories\Module;

use App\Models\Category;
use App\Repositories\BaseRepository;
use App\Services\Filter\CategoryFilter;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository extends BaseRepository
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
        $this->setFilter(new CategoryFilter(request()));
    }

    public function fetchCategoryByParent()
    {
        $cacheKey = $this->getCacheKey('fetchCategoryByParent');

        return $this->remember($cacheKey, function () {
            $query = $this->model->query();

            if ($this->tableHasColumn('is_active')) {
                $query->where('is_active', true);
            }

            $query->where('parent_id', 0);

            $this->applyRelations($query);
            return $query->get();
        });
    }

    /**
     * Frontend üçün aktiv parent kateqoriyaları gətirir.
     * Cache mexanizmi BaseRepository-dən gəlir.
     */
    public function getActiveParentCategories(): Collection
    {
        return $this->executeWithCache('getActiveParentCategories', function () {
            return $this->model->query()
                ->where('parent_id', 0)
                ->where('is_active', true)
                ->orderBy('order')
                ->select(['id', 'uuid', 'slug', 'translates', 'icon', 'photo_path'])
                ->get();
        });
    }

    /*
     *
     * */
    public function getChildrenByUUid($uuid)
    {
        return $this->executeWithCache('getChildrenByUUid' . $uuid, function () use ($uuid) {
            return $this->model->query()
                ->whereRelation('parent', 'uuid', $uuid)
                ->where('is_active', true)
                ->orderBy('order')
                ->get();
        });
    }

    /**
     * Frontend üçün seçilmiş parent-in aktiv alt kateqoriyalarını gətirir.
     * Lazy loading prinsipi ilə işləyir.
     */
    public function getActiveChildCategories(int $parentId): Collection
    {
        return $this->executeWithCache('getActiveChildCategories_' . $parentId, function () use ($parentId) {
            return $this->model->query()
                ->where('parent_id', $parentId)
                ->where('is_active', true)
                ->orderBy('order')
                ->select(['id', 'uuid', 'slug', 'translates', 'icon', 'photo_path'])
                ->get();
        });
    }

    /**
     * Admin filter dropdown-u üçün bütün parent kateqoriyaları gətirir
     */
    public function getAllParentCategories(): Collection
    {
        return $this->executeWithCache('getAllParentCategories', function () {
            return $this->model->query()
                ->where('parent_id', 0)
                ->select(['id', 'translates'])
                ->get();
        });
    }

    /**
     * Admin filter dropdown-u üçün bütün kateqoriyaları gətirir
     */
    public function getAllCategories(): Collection
    {
        $query = $this->model->newQuery();

        if ($this->filter) {
            $query = $this->filter->apply($query);
        }

        return $query
            ->select(['id', 'translates'])
            ->limit(50)
            ->get();
    }
}
