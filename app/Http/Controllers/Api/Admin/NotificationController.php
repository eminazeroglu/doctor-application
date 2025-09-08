<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\NotificationTypeEnum;
use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\NotificationResource;
use App\Models\User;
use App\Services\Module\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class NotificationController extends ApiController
{
    public function __construct(NotificationService $service)
    {
        parent::__construct($service, 'notification');
        $this->setResource(NotificationResource::class);
    }

    /**
     * Admin üçün notification siyahısını əldə etmək
     */
    public function index(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $data = $this->service->paginateAndFilter();
            return response()->json([
                'data' => $this->toResource($data),
                'total' => $data->total(),
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Yeni notification yaratmaq və göndərmək
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $validatedData = $this->validateRequest($request, $this->storeRules(), $this->storeMessages());

            try {
                // Tək istifadəçi üçün
                if (isset($validatedData['user_id'])) {
                    $user = User::findOrFail($validatedData['user_id']);
                    $notification = $this->service->send(
                        $user,
                        $validatedData['type'],
                        $validatedData,
                        $validatedData['priority'] ?? null,
                        isset($validatedData['send_at']) ? now()->parse($validatedData['send_at']) : null
                    );

                    return response()->json([
                        'message' => 'Notification uğurla göndərildi',
                        'data' => $this->toResource($notification)
                    ], 201);
                }

                // Kütləvi göndərmə
                if (isset($validatedData['user_ids'])) {
                    $result = $this->service->sendBulk(
                        $validatedData['user_ids'],
                        $validatedData['type'],
                        $validatedData
                    );

                    return response()->json([
                        'message' => 'Kütləvi notification göndərildi',
                        'result' => $result
                    ], 201);
                }

                return response()->json(['message' => 'İstifadəçi seçilməlidir'], 422);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Notification göndərmə xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Notification detallarını görmək
     */
    public function show($id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $notification = $this->service->findById($id);
            return response()->json([
                'data' => $this->toResource($notification),
                'logs' => $notification->logs ?? []
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Notification silmək
     */
    public function destroy($id): JsonResponse
    {
        if ($this->authorizeAction('delete')) {
            $result = $this->service->delete($id);
            return response()->json([
                'message' => 'Notification silindi',
                'deleted' => $result
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Kütləvi silmə
     *
     * @throws ValidationException
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        if ($this->authorizeAction('delete')) {
            $this->validateRequest($request, [
                'ids' => 'required|array',
                'ids.*' => 'required|integer|exists:notifications,id'
            ]);

            $deletedCount = 0;
            foreach ($request->ids as $id) {
                if ($this->service->delete($id)) {
                    $deletedCount++;
                }
            }

            return response()->json([
                'message' => "{$deletedCount} notification silindi",
                'deleted_count' => $deletedCount,
                'total_requested' => count($request->ids)
            ]);
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
                'notification_types' => collect(NotificationTypeEnum::getValues())->map(fn($type) => [
                    'value' => $type,
                    'label' => NotificationTypeEnum::getDescription($type)
                ])->values(),
                'users' => User::select('id', 'name', 'surname', 'email')
                    ->orderBy('name')
                    ->get()
                    ->map(fn($user) => [
                        'value' => $user->id,
                        'label' => $user->name . ' ' . $user->surname . ' (' . $user->email . ')'
                    ]),
                'channels' => [
                    ['value' => 'email', 'label' => 'E-poçt'],
                    ['value' => 'sms', 'label' => 'SMS'],
                    ['value' => 'push', 'label' => 'Push'],
                    ['value' => 'in_app', 'label' => 'Tətbiq daxili']
                ]
            ];

            return response()->json($filters);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Statistikalar
     */
    public function statistics(): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $stats = $this->service->getAdminStats();
            return response()->json($stats);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Planlaşdırılmış notification-ları dərhal göndərmək
     */
    public function sendScheduled(): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $count = $this->service->sendScheduledNotifications();
            return response()->json([
                'message' => "{$count} planlaşdırılmış notification göndərildi",
                'sent_count' => $count
            ]);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * İstifadəçi axtarışı notification göndərmək üçün
     */
    public function searchUsers(Request $request): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $search = $request->get('search', '');

            $users = User::where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
                ->select('id', 'name', 'surname', 'email', 'photo_path')
                ->limit(20)
                ->get()
                ->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name . ' ' . $user->surname,
                    'email' => $user->email,
                    'photo' => $user->photo
                ]);

            return response()->json($users);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Təşkilati üçün kütləvi notification göndərmək
     * @throws ValidationException
     */
    public function sendToAll(Request $request): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $validatedData = $this->validateRequest($request, [
                'type' => 'required|string|in:' . implode(',', NotificationTypeEnum::getValues()),
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'icon' => 'nullable|string',
                'action_url' => 'nullable|url',
                'action_text' => 'nullable|string|max:50',
                'send_at' => 'nullable|date|after:now',
                'exclude_user_ids' => 'nullable|array',
                'exclude_user_ids.*' => 'integer|exists:users,id'
            ]);

            try {
                $userQuery = User::query();

                if (isset($validatedData['exclude_user_ids'])) {
                    $userQuery->whereNotIn('id', $validatedData['exclude_user_ids']);
                }

                $userIds = $userQuery->pluck('id')->toArray();

                $result = $this->service->sendBulk(
                    $userIds,
                    $validatedData['type'],
                    $validatedData
                );

                return response()->json([
                    'message' => 'Bütün istifadəçilərə notification göndərildi',
                    'result' => $result
                ], 201);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Kütləvi notification göndərmə xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Store validation qaydaları
     */
    public function storeRules(): array
    {
        return [
            'type' => 'required|string|in:' . implode(',', NotificationTypeEnum::getValues()),
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'icon' => 'nullable|string',
            'action_url' => 'nullable|url',
            'action_text' => 'nullable|string|max:50',
            'priority' => 'nullable|string|in:low,normal,high',
            'send_at' => 'nullable|date|after:now',

            // Tək istifadəçi üçün
            'user_id' => 'nullable|integer|exists:users,id|required_without:user_ids',

            // Kütləvi göndərmə üçün
            'user_ids' => 'nullable|array|required_without:user_id',
            'user_ids.*' => 'integer|exists:users,id'
        ];
    }

    /**
     * Store validation mesajları
     */
    public function storeMessages(): array
    {
        return [
            'type.required' => 'Notification növü tələb olunur',
            'type.in' => 'Yalnış notification növü',
            'title.required' => 'Başlıq tələb olunur',
            'content.required' => 'Məzmun tələb olunur',
            'user_id.required_without' => 'İstifadəçi seçilməlidir',
            'user_ids.required_without' => 'Ən azı bir istifadəçi seçilməlidir',
            'send_at.after' => 'Göndərmə tarixi gələcəkdə olmalıdır',
        ];
    }
}
