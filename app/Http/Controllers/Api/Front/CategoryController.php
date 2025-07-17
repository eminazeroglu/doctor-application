<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
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

    public function categoryWithChildren($uuid): \Illuminate\Http\JsonResponse
    {
        $categories = $this->repository->getChildrenByUUid($uuid);
        return response()->json(CategoryResource::collection($categories));
    }

    public function categoryWithAttribute($uuid): \Illuminate\Http\JsonResponse
    {
        $categories = $this->repository->getCategoryWithAttributesByUuid($uuid);
        return response()->json(AttributeResource::collection($categories));
    }
}
