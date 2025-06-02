<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\AttributePositionEnum;
use App\Enums\AttributeTypeEnum;
use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\AttributeTreeResource;
use App\Services\Module\AttributeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttributeController extends ApiController
{
    public function __construct(AttributeService $service)
    {
        parent::__construct($service, 'attribute');
        $this->treeResourceClass = AttributeTreeResource::class;
    }

    /**
     * Atribut optionlarını listələyir
     */
    public function listOptions($id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $result = $this->service->attributeOptions($id);
            return response()->json($result);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Atribut optionlarını sinxronlaşdırır
     * @throws ValidationException
     */
    public function syncOptions(Request $request, int $id)
    {
        if ($this->authorizeAction('update')) {

            $attribute = $this->service->findById($request->route('id'));

            $this->validateRequest($request, [
                'translates' => 'required|array',
                'translates.*' => 'required',
                'translates.*.name' => 'required|string|max:255',
                'parent_id' => $attribute->has_dependent_options ? 'required|integer|exists:attribute_options,id' : 'nullable',
            ]);

            $result = $this->service->syncAttributeOptions($id, $request->all());
            return response()->json($result);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Atribut option-ı silmək
     * */
    public function deleteOption($id, $optionId): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $result = $this->service->deleteOption($id, $optionId);
            return response()->json($result);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Atribute optionlarını sıralamaq
     * */
    public function orderOption(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $result = $this->service->orderOption($id, $request->all());
            return response()->json($result);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Atribute optionlarını statuslarını dəyişmək
     * */
    public function statusOption(Request $request, $id, $orderId): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $result = $this->service->statusOption($id, $orderId, $request->all());
            return response()->json($result);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    public function commonRules(): array
    {
        return [
            'translates' => 'required|array',
            'translates.*.name' => 'required|string|max:255',
           // 'translates.*.description' => 'required|string',
            'type' => 'required|string|in:' . implode(',', AttributeTypeEnum::getValues()),
        ];
    }
}
