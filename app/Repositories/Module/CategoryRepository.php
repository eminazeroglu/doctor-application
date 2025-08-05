<?php

namespace App\Repositories\Module;

use App\Models\Category;
use App\Repositories\BaseRepository;
use App\Services\Filter\CategoryFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public function filters(): array
    {
        return [

        ];
    }

    /**
     * Frontend üçün aktiv parent kateqoriyaları gətirir.
     * Cache mexanizmi BaseRepository-dən gəlir.
     */
    public function getActiveParentCategories($withChildren = false): Collection
    {
        return $this->executeWithCache('getActiveParentCategories', function () use ($withChildren) {
            return $this->model->query()
                ->when($withChildren, function ($q) {
                    return $q->with('children');
                })
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
     * Kateqoriyanı bütün atribut və qaydaları ilə birlikdə gətirir
     */
    public function getCategoryWithAttributesByUuid(string $uuid)
    {
        $is_visible = request()->get('is_visible');
        return $this->executeWithCache('getCategoryWithAttributes_' . $uuid, function () use ($uuid, $is_visible) {
            $category = $this->model->query()
                ->with(['attributes' => function ($q) use ($is_visible) {
                    $q->when($is_visible, function ($q) {
                        $q->where('is_active', true);
                    })
                        ->oldest('order')
                        ->with(['attribute' => function ($q) {
                            $q->with(['options' => function ($q) {
                                $q->where('is_active', true);
                            }]);
                        }]);
                }])
                ->where('uuid', $uuid)
                ->firstOrFail();

            return $category->attributes;
        });
    }

    /**
     * Kateqoriyanı bütün xidmətləri birlikdə gətirir
     */
    public function getCategoryWithServicesByUuid(string $uuid)
    {
        return $this->executeWithCache('getCategoryWithServices_' . $uuid, function () use ($uuid) {
            $category = $this->model->query()
                ->where('uuid', $uuid)
                ->firstOrFail();
            return $category->services;
        });
    }

    public function getCategoryWithTermsByUuid($uuid)
    {
        return $this->executeWithCache('getCategoryWithTerms_' . $uuid, function () use ($uuid) {
            $category = $this->model->query()
                ->with([
                    'terms' => function ($q) {
                        $q->where('is_active', true);
                    }
                ])
                ->where('uuid', $uuid)
                ->firstOrFail();

            return $category->terms;
        });
    }

    /**
     * Kateqoriyanı bütün atribut və qaydaları ilə birlikdə gətirir
     */
    public function getCategoryWithAttributes(int $id)
    {
        $is_visible = request()->get('is_visible');
        return $this->executeWithCache('getCategoryWithAttributes_' . $id, function () use ($id, $is_visible) {
            $category = $this->model->query()
                ->with(['attributes' => function ($q) use ($is_visible) {
                    $q->when($is_visible, function ($q) {
                        $q->where('is_active', true);
                    })
                        ->oldest('order')
                        ->with(['attribute' => function ($q) {
                            $q->with(['options' => function ($q) {
                                $q->where('is_active', true);
                            }]);
                        }]);
                }])
                ->findOrFail($id);

            return $category->attributes;
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
            ->with('children')
            ->limit(50)
            ->get();
    }

    public function attachAttribute(int $categoryId, array $data): Model
    {
        return $this->executeWithCache("attachAttribute_{$categoryId}_{$data['attribute_id']}", function () use ($categoryId, $data) {
            $category = $this->findById($categoryId);

            $lastOrderId = $category->attributes()->max('order') ?? 0;

            $result = [
                'validation_rules' => $data['validation_rules'] ?? null,
                'is_required' => $data['is_required'] ?? false,
                'is_visible' => $data['is_visible'] ?? true,
                'custom_fields' => $data['custom_fields'] ?? null
            ];

            if (!request()->input('id')) {
                $result['order'] = $lastOrderId + 1;
            }

            $category->attributes()->updateOrCreate(
                [
                    'category_id' => $categoryId,
                    'attribute_id' => $data['attribute_id'],
                ],
                $result
            );

            return $category->fresh(['attributes']);
        });
    }

    public function detachAttribute(int $categoryId, int $attributeId): bool
    {
        return $this->executeWithCache("detachAttribute_{$categoryId}_{$attributeId}", function () use ($categoryId, $attributeId) {
            $category = $this->findById($categoryId);
            return $category->attributes()->detach($attributeId) > 0;
        });
    }

    public function attributeOrder(int $attributeId, array $orders): bool
    {
        $model = $this->findById($attributeId);

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
                $sortedItems->values()->each(function ($item, $index) use ($model, $orderValue) {
                    $option = $model->attributes()->find($item['id']);
                    if ($option) {
                        // Eyni order dəyərinə sahib elementlər üçün base_order + index
                        // məsələn 0 + 1, 0 + 2 kimi
                        $newOrder = (int)$orderValue + $index;
                        $option->update(['order' => $newOrder]);
                    }
                });
            }

            DB::commit();

            if ($this->useCache) {
                $this->clearCache();
            }

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return false;
        }
    }

    /**
     * Config
     */
    public function config($id): array
    {
        $data = $this->model->with(['relateds', 'votingSystems'])->findOrFail($id);
        return [
            'categories' => $data->relateds->pluck('related_id')->toArray(),
            'voting_systems' => $data->votingSystems->pluck('voting_system_id')->toArray(),
        ];
    }

    /**
     * Config Save
     */
    public function configSave($id, array $data): true
    {
        $category = $this->findById($id);
        $category->syncRelations($data);
        return true;
    }
}
