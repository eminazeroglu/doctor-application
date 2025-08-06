<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ReferenceResource;
use App\Http\Resources\Front\AttributeResource;
use App\Http\Resources\Front\CategoryResource;
use App\Repositories\Module\CategoryRepository;

class CategoryController extends Controller
{
    public CategoryRepository $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function categoryOnlyParent(): \Illuminate\Http\JsonResponse
    {
        $categories = $this->repository->fetchCategoryByParent();
        return response()->json(CategoryResource::collection($categories));
    }

    public function categoryWithChildren($id): \Illuminate\Http\JsonResponse
    {
        $categories = $this->repository->getChildrenById($id);
        return response()->json(CategoryResource::collection($categories));
    }

    public function categoryWithAttribute($id): \Illuminate\Http\JsonResponse
    {
        $categories = $this->repository->getCategoryWithAttributesById($id);
        return response()->json(AttributeResource::collection($categories));
    }

    public function categoryWithServices($uuid): \Illuminate\Http\JsonResponse
    {
        $categories = $this->repository->getCategoryWithServicesByUuid($uuid);
        return response()->json(ReferenceResource::collection($categories));
    }
}
