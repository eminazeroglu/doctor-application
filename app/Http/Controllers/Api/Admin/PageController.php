<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PageTypeEnum;
use App\Enums\WidgetTypeEnum;
use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\PageResource;
use App\Http\Resources\Admin\PageWidgetResource;
use App\Rules\Base64ImageControlRule;
use App\Rules\ImageBase64Rule;
use App\Services\Module\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PageController extends ApiController
{
    public function __construct(PageService $service)
    {
        parent::__construct($service, 'page');
        $this->setResource(PageResource::class);
    }

    public function commonRules(): array
    {
        return [
            'translates' => 'required|array',
            'translates.*.name' => 'required|string|max:255',
            'translates.*.content' => 'nullable|string',
            'type' => ['required', Rule::in(PageTypeEnum::getValues())],
            'is_active' => 'boolean',
            'photo_path' => ['nullable', new ImageBase64Rule, new Base64ImageControlRule]
        ];
    }

    /**
     * Widget yaratmaq
     */
    public function createWidget(Request $request): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $this->validateRequest($request, [
                'page_id' => 'required|exists:pages,id',
                'type' => ['required', Rule::in(WidgetTypeEnum::getValues())],
                'data' => 'nullable|array',
                'photo_path' => ['nullable', new ImageBase64Rule, new Base64ImageControlRule],
                'translates' => 'required|array',
                'translates.*.title' => 'required|string|max:255',
                'translates.*.description' => 'nullable|string',
                'translates.*.button_text' => 'nullable|string|max:255'
            ]);

            $widget = $this->service->createWidget($request->all());

            return response()->json(new PageWidgetResource($widget), 201);
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Widget yeniləmək
     */
    public function updateWidget(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $this->validateRequest($request, [
                'order' => 'nullable|integer',
                'is_active' => 'boolean',
                'data' => 'nullable|array',
                'photo_path' => ['nullable', new ImageBase64Rule, new Base64ImageControlRule],
                'translates' => 'required|array',
                'translates.*.title' => 'required|string|max:255',
                'translates.*.description' => 'nullable|string',
                'translates.*.button_text' => 'nullable|string|max:255'
            ]);

            $widget = $this->service->updateWidget($id, $request->all());

            return response()->json(new PageWidgetResource($widget));
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Widget silmək
     */
    public function deleteWidget($id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $result = $this->service->deleteWidget($id);

            return response()->json(['success' => $result]);
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Widget-ın statusunu dəyişmək
     */
    public function changeWidgetStatus($id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $widget = $this->service->changeWidgetStatus($id);

            return response()->json(new PageWidgetResource($widget));
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Səhifəyə aid widget-ları almaq
     */
    public function getWidgets($pageId): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $widgets = $this->service->findWidgetsByPageId($pageId);

            return response()->json(PageWidgetResource::collection($widgets));
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Widget-ı ID ilə almaq
     */
    public function getWidget($id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $widget = $this->service->findWidgetById($id);

            return response()->json(new PageWidgetResource($widget));
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Widget-ların sırasını dəyişmək
     */
    public function reorderWidgets(Request $request): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $this->validateRequest($request, [
                'orders' => 'required|array',
                'orders.*.id' => 'required|exists:page_widgets,id',
                'orders.*.order' => 'required|integer'
            ]);

            $result = $this->service->updateWidgetsOrder($request->orders);

            return response()->json(['success' => $result]);
        }

        return response()->json(['message' => $this->forbiddenMessage], 403);
    }
}
