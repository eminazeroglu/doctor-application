<?php

namespace App\Repositories\Module;

use App\Http\Resources\Admin\AttributeOptionResource;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Repositories\BaseRepository;
use App\Services\Filter\AttributeFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class AttributeRepository extends BaseRepository
{
    public function __construct(Attribute $model)
    {
        parent::__construct($model);
        $this->setFilter(new AttributeFilter(request()));
    }

    public function afterUpdate($model): void
    {
        Artisan::call("repo:clear");
    }

    public function afterCreate($model): void
    {
        Artisan::call("repo:clear");
    }

    /**
     * Kateqoriyaya atribut əlavə edir
     */
    public function addToCategory(int $categoryId, array $data): bool
    {
        return $this->executeWithCache("addToCategory_{$categoryId}", function () use ($categoryId, $data) {
            $model = $this->findById($data['attribute_id']);

            return $model->categories()->attach($categoryId, [
                'validation_rules' => $data['validation_rules'] ?? null,
                'is_required' => $data['is_required'] ?? false,
                'is_visible' => $data['is_visible'] ?? true,
                'order' => $data['order'] ?? 0,
                'custom_fields' => $data['custom_fields'] ?? null
            ]);
        });
    }

    /**
     * Kateqoriyadan atributu silir
     */
    public function removeFromCategory(int $categoryId, int $attributeId): bool
    {
        return $this->executeWithCache("removeFromCategory_{$categoryId}_{$attributeId}", function () use ($categoryId, $attributeId) {
            $model = $this->findById($attributeId);
            return $model->categories()->detach($categoryId);
        });
    }

    /**
     * Atributun optionlarını sinxronlaşdırır.
     * Bu method mövcud optionları yeniləyir və yenilərini əlavə edir.
     */
    public function syncOptions(int $attributeId, array $request)
    {
        return $this->executeWithCache("syncOptions_{$attributeId}", function () use ($attributeId, $request) {
            $model = $this->findById($attributeId);

            // Options array-ini işləyirik
            $data = [
                'parent_id' => $request['parent_id'] ?? null,
                'translates' => $request['translates'],
                'custom_fields' => $request['custom_fields'] ?? null,
                'is_default' => $request['is_default'] ?? false,
                'is_active' => $request['is_active'] ?? true
            ];

            if (!isset($request['id'])) {
                $lastOrderId = $model->options()->max('order') ?? 0;
                $data['order'] = $lastOrderId + 1;
            }

            if (isset($request['id'])) {
                return $model->options()
                    ->where('id', $request['id'])
                    ->update($data);
            } else {
                return $model->options()->create($data);
            }
        });
    }

    /**
     * Atribut optionunu silir.
     * Bu method option-un istifadədə olub-olmadığını yoxlayır.
     * Əgər istifadədədirsə xəta qaytarır.
     */
    public function deleteOption(int $attributeId, int $optionId): bool
    {
        return $this->executeWithCache("deleteOption_{$attributeId}_{$optionId}", function () use ($attributeId, $optionId) {
            $model = $this->findById($attributeId);

            // Optionu tapırıq
            $option = $model->options()->findOrFail($optionId);

            return $option->delete();
        });
    }

    /**
     * Attribute option sıralama
     * */
    public function orderOption(int $attributeId, array $orders): bool
    {
        $model = $this->findById($attributeId);

        foreach ($orders as $order) {
            if (!isset($order['id']) || !isset($order['order'])) {
                throw new \InvalidArgumentException(
                    'Invalid order data structure. Each item must contain id and order fields.'
                );
            }
        }

        try {
            DB::beginTransaction();

            foreach ($orders as $order) {
                $option = $model->options()->find($order['id']);
                if ($option) {
                    $option->update(['order' => $order['order']]);
                }
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
     * Attribute option statusunu dəyişir
     * */
    public function statusOption(int $attributeId, int $orderId, array $request)
    {
        $attribute = $this->findById($attributeId);

        $statusField = $request['action'] ?? 'is_active';

        $model = $attribute->options()->findOrFail($orderId);

        if ($statusField === 'is_default') {
            if ($attribute->has_dependent_options) {
                $attribute->options()->where('parent_id', $model->parent_id)->update(['is_default' => false]);
            }
            else {
                $attribute->options()->update(['is_default' => false]);
            }
        }

        $model->$statusField = !$model->$statusField;
        $model->save();

        return $model;
    }

    /**
     * Atributun optionlarını qaytarır
     */
    public function attributeOptions(int $attributeId)
    {
        return $this->executeWithCache("attributeOptions_{$attributeId}", function () use ($attributeId) {
            $attribute = $this->findById($attributeId);
            $query = $attribute->options();

            if ($attribute->has_dependent_options) {
                // Bütün datanı bir sorğuda əldə edirik
                $data = AttributeOption::query()
                    ->whereIn('id', function ($q) use ($attributeId) {
                        // Parent ID-ləri subquery ilə alırıq
                        $q->select('parent_id')
                            ->from('attribute_options')
                            ->where('attribute_id', $attributeId)
                            ->whereNotNull('parent_id');
                    })
                    ->with(['children' => function ($q) use ($attributeId) {
                        // Children-ləri filter edirik
                        $q->where('attribute_id', $attributeId)
                            ->orderBy('order');
                    }])
                    ->orderBy('order')
                    ->get();

                return AttributeOptionResource::collection($data);
            }

            $data = $query->with('parent')
                ->oldest('order')
                ->get();

            return AttributeOptionResource::collection($data);
        });
    }

    /**
     * Atributun aktiv optionlarını qaytarır
     */
    public function getActiveOptions(int $attributeId): Collection
    {
        return $this->executeWithCache("getActiveOptions_{$attributeId}", function () use ($attributeId) {
            return $this->findById($attributeId)
                ->options()
                ->where('is_active', true)
                ->oldest('order')
                ->get();
        });
    }

    /**
     * Parent optiona bağlı olan bütün aktiv child optionları qaytarır.
     * Bu method universal parent-child əlaqəsi üçün istifadə olunur.
     */
    public function getDependentOptions(int $parentOptionId)
    {
        return $this->executeWithCache("getDependentOptions_{$parentOptionId}", function() use ($parentOptionId) {
            return AttributeOption::query()
                ->where('attribute_id', $parentOptionId)
                ->where('is_active', true)
                ->orderBy('order')
                ->get();
        });
    }
}
