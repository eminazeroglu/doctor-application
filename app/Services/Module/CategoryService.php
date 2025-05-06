<?php

namespace App\Services\Module;

use App\Repositories\Module\CategoryRepository;
use App\Services\BaseCrudService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CategoryService extends BaseCrudService
{
    public function __construct(CategoryRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Ana kateqoriyaları gətirir. Frontend dropdownlar üçün.
     * Bu method sadəcə parent_id = 0 olan kateqoriyaları gətirəcək.
     */
    public function getParentCategories(): Collection
    {
        return $this->repository->getActiveParentCategories();
    }

    /**
     * Seçilmiş parent-in active olan alt kateqoriyalarını gətirir.
     * Lazy loading prinsipi ilə işləyir.
     */
    public function getChildCategories(int $parentId): Collection
    {
        return $this->repository->getActiveChildCategories($parentId);
    }

    /**
     * Kateqoriyanı bütün atributları ilə birlikdə gətirir.
     * Burda həm atributlar, həm validation qaydaları, həm də options gələcək.
     */
    public function getCategoryWithAttributes(int $id)
    {
        return $this->repository->getCategoryWithAttributes($id);
    }

    public function attachAttribute(int $categoryId, array $data): Model
    {
        return $this->repository->attachAttribute($categoryId, $data);
    }

    public function attributeOrder($id, $orders)
    {
        return $this->repository->attributeOrder($id, $orders);
    }

    public function updateAttribute(int $categoryId, int $attributeId, array $data): Model
    {
        return $this->repository->updateAttribute($categoryId, $attributeId, $data);
    }

    public function detachAttribute(int $categoryId, int $attributeId): bool
    {
        return $this->repository->detachAttribute($categoryId, $attributeId);
    }

    /**
     * Config
     * */
    public function config($id)
    {
        return $this->repository->config($id);
    }

    /**
     * Config Save
     */
    public function configSave($id, array $data)
    {
        return $this->repository->configSave($id, $data);
    }
}
