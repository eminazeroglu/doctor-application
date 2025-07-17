<?php

namespace App\Services\Module;

use App\Enums\AttributePositionEnum;
use App\Enums\AttributeTypeEnum;
use App\Repositories\Module\AttributeRepository;
use App\Services\BaseCrudService;
use Illuminate\Database\Eloquent\Collection;

class AttributeService extends BaseCrudService
{
    public function __construct(AttributeRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Seçilmiş kateqoriyaya aid atribut əlavə edir
     */
    public function addAttributeToCategory(int $categoryId, array $data): bool
    {
        return $this->repository->addToCategory($categoryId, $data);
    }

    /**
     * Kateqoriyadan atributu silir
     */
    public function removeAttributeFromCategory(int $categoryId, int $attributeId): bool
    {
        return $this->repository->removeFromCategory($categoryId, $attributeId);
    }

    /**
     * Atributun optionlarını listələyir
     */
    public function attributeOptions(int $attributeId)
    {
        return $this->repository->attributeOptions($attributeId);
    }

    /**
     * Atributun optionlarını əlavə edir/yeniləyir
     */
    public function syncAttributeOptions(int $attributeId, array $request)
    {
        return $this->repository->syncOptions($attributeId, $request);
    }

    /**
     * Attributun option-ı islmək
     * */
    public function deleteOption($id, $optionId)
    {
        return $this->repository->deleteOption($id, $optionId);
    }

    /**
     * Attributun option-ı sıralamaq
     * */
    public function orderOption($id, $optionId)
    {
        return $this->repository->orderOption($id, $optionId);
    }

    /**
     * Attributun option-ı statusunu dəyişmək
     * */
    public function statusOption(int $id, int $orderId, array $request)
    {
        return $this->repository->statusOption($id, $orderId, $request);
    }

    /**
     * Admin filter seçimlərini qaytarır
     */
    public function filters(): array
    {
        return [
            'types' => $this->getAttributeTypes(),
            'positions' => $this->getAttributePositions()
        ];
    }

    /**
     * Atribut tiplərini qaytarır
     */
    public function getAttributeTypes(): \Illuminate\Support\Collection
    {
        return collect(AttributeTypeEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => AttributeTypeEnum::getDescription($i)
        ]);
    }

    /**
     * Atribut tiplərini qaytarır
     */
    public function getAttributePositions(): \Illuminate\Support\Collection
    {
        return collect(AttributePositionEnum::getValues())->map(fn($i) => [
            'id' => $i,
            'name' => AttributePositionEnum::getDescription($i)
        ]);
    }
}
