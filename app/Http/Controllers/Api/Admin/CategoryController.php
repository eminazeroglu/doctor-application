<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\CategoryAttributeResource;
use App\Http\Resources\Admin\CategoryResource;
use App\Rules\Base64ImageControlRule;
use App\Rules\ImageBase64Rule;
use App\Services\Module\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoryController extends ApiController
{
    public function __construct(CategoryService $service)
    {
        parent::__construct($service, 'category');
        $this->setResource(CategoryResource::class);
    }

    public function commonRules(): array
    {
        return [
            'translates' => ['required', 'array'],
            'translates.*.name' => ['required', 'string', 'max:255'],
            'translates.*.description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'terms_id' => ['nullable', 'integer', 'exists:terms,id'],
            'icon' => ['nullable', 'string'],
            'photo_path' => ['nullable', 'string', new ImageBase64Rule, new Base64ImageControlRule],
            'meta_tags' => ['nullable', 'array'],
        ];
    }

    /**
     * Parent kateqoriyanın altında olan kateqoriyaları qaytarır.
     * Frontend üçün lazy loading.
     */
    public function children(int $parentId): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $children = $this->service->getChildCategories($parentId);
            return response()->json($this->toResource($children));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Kateqoriyanın bütün atributlarını qaytarır.
     * Elan əlavə etmə/redaktə üçün.
     */
    public function attributes(int $id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $category = $this->service->getCategoryWithAttributes($id);
            return response()->json(CategoryAttributeResource::collection($category));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Attributeların sıralaması
    */
    public function attributeOrder(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $result = $this->service->attributeOrder($id, $request->all());
            return response()->json($result);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * @throws ValidationException
     */
    public function attachAttribute(Request $request, int $categoryId): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $validated = $this->validateRequest($request, [
                'attribute_id' => ['required', 'integer', 'exists:attributes,id'],
                'validation_rules' => 'nullable|array',
                'is_required' => 'boolean',
                'is_visible' => 'boolean',
                'order' => 'integer',
                'custom_fields' => 'nullable|array'
            ]);

            $result = $this->service->attachAttribute($categoryId, $validated);
            return response()->json($this->toResource($result));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * @throws ValidationException
     */
    public function updateAttribute(Request $request, int $categoryId, int $attributeId): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $validated = $this->validateRequest($request, [
                'validation_rules' => 'nullable|array',
                'is_required' => 'boolean',
                'is_visible' => 'boolean',
                'order' => 'integer',
                'custom_fields' => 'nullable|array'
            ]);

            $result = $this->service->updateAttribute($categoryId, $attributeId, $validated);
            return response()->json($this->toResource($result));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    public function detachAttribute(int $categoryId, int $attributeId): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $result = $this->service->detachAttribute($categoryId, $attributeId);
            return response()->json(['success' => $result]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    public function config($id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            return response()->json($this->service->config($id));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    public function configSave(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            return response()->json($this->service->configSave($id, $request->all()));
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

}
