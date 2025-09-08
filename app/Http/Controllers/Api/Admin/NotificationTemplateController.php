<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTemplateTypeEnum;
use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\NotificationTemplateResource;
use App\Models\NotificationTemplate;
use App\Services\Module\NotificationTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotificationTemplateController extends ApiController
{
    public function __construct(NotificationTemplateService $service)
    {
        parent::__construct($service, 'notification_template');
        $this->setResource(NotificationTemplateResource::class);
    }

    /**
     * Template siyahısı
     */
    public function index(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $data = $this->service->paginateAndFilter();
            return response()->json([
                'data' => $this->toResource($data),
                'total' => (request()->has('tree') || request()->has('full')) ? $data->count() : $data->total()
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Yeni template yaratmaq
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $validatedData = $this->validateRequest($request, $this->storeRules(), $this->storeMessages());

            try {
                $template = $this->service->create($validatedData);

                return response()->json([
                    'message' => 'Template uğurla yaradıldı',
                    'data' => $this->toResource($template)
                ], 201);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Template yaratma xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Template detalları
     */
    public function show($id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $template = $this->service->findById($id);
            return response()->json([
                'data' => $this->toResource($template)
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Template yeniləmək
     *
     * @throws ValidationException
     */
    public function update(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('update')) {
            $validatedData = $this->validateRequest($request, $this->updateRules(), $this->updateMessages());

            try {
                $template = $this->service->update($id, $validatedData);

                return response()->json([
                    'message' => 'Template uğurla yeniləndi',
                    'data' => $this->toResource($template)
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Template yeniləmə xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Template silmək
     */
    public function destroy($id): JsonResponse
    {
        if ($this->authorizeAction('delete')) {
            try {
                $result = $this->service->delete($id);
                return response()->json([
                    'message' => 'Template silindi',
                    'deleted' => $result
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Template silmə xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Template status dəyişdirmək (aktiv/deaktiv)
     */
    public function action(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('status')) {
            try {
                $template = $this->service->changeStatus($id, 'is_active');

                return response()->json([
                    'message' => 'Template statusu dəyişdirildi',
                    'data' => $this->toResource($template)
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Status dəyişdirmə xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Filterlər üçün məlumatlar
     */
    public function filters(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $filters = [
                'channels' => collect(NotificationChannelEnum::getValues())->map(fn($channel) => [
                    'value' => $channel,
                    'label' => NotificationChannelEnum::getDescription($channel)
                ])->values(),
                'types' => collect(NotificationTemplateTypeEnum::getValues())->map(fn($type) => [
                    'value' => $type,
                    'label' => NotificationTemplateTypeEnum::getDescription($type)
                ])->values(),
                'statuses' => [
                    ['value' => true, 'label' => 'Aktiv'],
                    ['value' => false, 'label' => 'Deaktiv']
                ]
            ];

            return response()->json($filters);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Template preview/test üçün məzmunu parse etmək
     */
    public function preview(Request $request, $id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $request->validate([
                'variables' => 'nullable|array'
            ]);

            try {
                $template = $this->service->findById($id);
                $variables = $request->get('variables', []);

                $parsedContent = $template->parseContent($variables);
                $parsedSubject = $template->parseSubject($variables);

                return response()->json([
                    'subject' => $parsedSubject,
                    'content' => $parsedContent,
                    'original_subject' => $template->subject,
                    'original_content' => $template->content,
                    'variables' => $template->variables
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Template preview xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Template kodu üçün unique check
     */
    public function checkCode(Request $request): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $request->validate([
                'code' => 'required|string',
                'id' => 'nullable|integer'
            ]);

            $query = NotificationTemplate::where('code', $request->code);

            if ($request->id) {
                $query->where('id', '!=', $request->id);
            }

            $exists = $query->exists();

            return response()->json([
                'available' => !$exists,
                'message' => $exists ? 'Bu kod artıq istifadə olunur' : 'Kod istifadə üçün uyğundur'
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Store validation qaydaları
     */
    public function storeRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:notification_templates,code',
            'channel' => 'required|string|in:' . implode(',', NotificationChannelEnum::getValues()),
            'type' => 'required|string|in:' . implode(',', NotificationTemplateTypeEnum::getValues()),
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean'
        ];
    }

    /**
     * Update validation qaydaları
     */
    public function updateRules(): array
    {
        $id = request()->route('id');
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:notification_templates,code,' . $id,
            'channel' => 'required|string|in:' . implode(',', NotificationChannelEnum::getValues()),
            'type' => 'required|string|in:' . implode(',', NotificationTemplateTypeEnum::getValues()),
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean'
        ];
    }

    /**
     * Store validation mesajları
     */
    public function storeMessages(): array
    {
        return [
            'name.required' => 'Template adı tələb olunur',
            'code.required' => 'Template kodu tələb olunur',
            'code.unique' => 'Bu kod artıq istifadə olunur',
            'channel.required' => 'Kanal seçilməlidir',
            'channel.in' => 'Yalnış kanal seçimi',
            'type.required' => 'Template növü seçilməlidir',
            'type.in' => 'Yalnış template növü',
            'content.required' => 'Template məzmunu tələb olunur'
        ];
    }

    /**
     * Update validation mesajları
     */
    public function updateMessages(): array
    {
        return $this->storeMessages();
    }
}
