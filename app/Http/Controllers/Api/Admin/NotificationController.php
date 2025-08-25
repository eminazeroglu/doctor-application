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
                'stats' => $this->service->getAdminStats()
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

    /**
     * İstifadəçiyə aid bildirişləri qaytarır
     * GET /api/app/notifications/user
     */
    public function userNotifications(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $filters = $request->only(['type', 'read']);

            $notifications = $this->service->getUserNotifications($user->id, $filters);

            return response()->json([
                'data' => NotificationResource::collection($notifications->items()),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
                'unread_count' => $this->service->getUnreadCount($user)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Notification-lar əldə edilə bilmədi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Notification-ı oxunmuş kimi qeyd etmək
     * POST /api/app/notifications/{id}/read
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $isMarked = $this->service->markAsRead($id, $user->id);

            if ($isMarked) {
                return response()->json([
                    'message' => 'Notification oxunmuş kimi qeyd edildi',
                    'unread_count' => $this->service->getUnreadCount($user)
                ]);
            }

            return response()->json(['message' => 'Xəta baş verdi'], 500);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Bütün notification-ları oxunmuş kimi qeyd etmək
     * POST /api/app/notifications/read-all
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $user = Auth::user();
            $markedCount = $this->service->markAllAsRead($user);

            return response()->json([
                'message' => "{$markedCount} notification oxunmuş kimi qeyd edildi",
                'marked_count' => $markedCount,
                'unread_count' => 0
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Xəta baş verdi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * İstifadəçi notification statistikaları
     * GET /api/app/notifications/user/stats
     */
    public function userStats(): JsonResponse
    {
        try {
            $user = Auth::user();
            $stats = $this->service->getStats($user);

            return response()->json($stats);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Statistika əldə edilə bilmədi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===================================================
    // Əlavə metodlar (əgər genişləndirilmiş route-lar istifadə olunarsa)
    // ===================================================

    /**
     * Son notification-ları əldə etmək (real-time üçün)
     * GET /api/app/notifications/user/latest
     */
    public function getLatest(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $limit = $request->get('limit', 5);

            $notifications = $user->notifications()
                ->latest()
                ->limit($limit)
                ->get();

            return response()->json([
                'notifications' => NotificationResource::collection($notifications),
                'unread_count' => $this->service->getUnreadCount($user)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Son notification-lar əldə edilə bilmədi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Notification-ı silmək
     * DELETE /api/app/notifications/{id}
     */
    public function deleteNotification($id): JsonResponse
    {
        try {
            $user = Auth::user();
            $notification = $user->notifications()->find($id);

            if (!$notification) {
                return response()->json(['message' => 'Notification tapılmadı'], 404);
            }

            $notification->delete();

            return response()->json([
                'message' => 'Notification silindi',
                'unread_count' => $this->service->getUnreadCount($user)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Notification silinə bilmədi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Push notification üçün cihaz qeydiyyatı
     * POST /api/app/notifications/devices/register
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $request->validate([
            'device_token' => 'required|string',
            'device_type' => 'required|string|in:ios,android,web',
            'device_name' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50'
        ]);

        try {
            $user = Auth::user();
            $device = $this->service->registerDevice($user->id, $request->all());

            return response()->json([
                'message' => 'Cihaz uğurla qeydiyyatdan keçdi',
                'device_id' => $device->id
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cihaz qeydiyyatı xətası',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cihazı deaktiv etmək
     * POST /api/app/notifications/devices/unregister
     */
    public function unregisterDevice(Request $request): JsonResponse
    {
        $request->validate([
            'device_token' => 'required|string'
        ]);

        try {
            $user = Auth::user();
            $device = \App\Models\NotificationDevice::where('user_id', $user->id)
                ->where('device_token', $request->device_token)
                ->first();

            if ($device) {
                $device->deactivate();
                return response()->json(['message' => 'Cihaz deaktiv edildi']);
            }

            return response()->json(['message' => 'Cihaz tapılmadı'], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Xəta baş verdi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * İstifadəçinin cihazlarını əldə etmək
     * GET /api/app/notifications/devices
     */
    public function getUserDevices(): JsonResponse
    {
        try {
            $user = Auth::user();
            $devices = \App\Models\NotificationDevice::where('user_id', $user->id)
                ->orderByDesc('last_used_at')
                ->get(['id', 'device_type', 'device_name', 'is_active', 'last_used_at']);

            return response()->json(['devices' => $devices]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cihazlar əldə edilə bilmədi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Notification tənzimləmələrini əldə etmək
     * GET /api/app/notifications/preferences
     */
    public function getPreferences(): JsonResponse
    {
        try {
            $user = Auth::user();
            $preferences = $user->notificationPreferences()
                ->get(['notification_type', 'email_enabled', 'sms_enabled', 'push_enabled', 'in_app_enabled']);

            return response()->json(['preferences' => $preferences]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Tənzimləmələr əldə edilə bilmədi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Notification tənzimləmələrini yeniləmək
     * PUT /api/app/notifications/preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.notification_type' => 'required|string',
            'preferences.*.email_enabled' => 'required|boolean',
            'preferences.*.sms_enabled' => 'required|boolean',
            'preferences.*.push_enabled' => 'required|boolean',
            'preferences.*.in_app_enabled' => 'required|boolean',
        ]);

        try {
            $user = Auth::user();

            foreach ($request->preferences as $preference) {
                $user->notificationPreferences()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'notification_type' => $preference['notification_type']
                    ],
                    [
                        'email_enabled' => $preference['email_enabled'],
                        'sms_enabled' => $preference['sms_enabled'],
                        'push_enabled' => $preference['push_enabled'],
                        'in_app_enabled' => $preference['in_app_enabled'],
                    ]
                );
            }

            return response()->json(['message' => 'Tənzimləmələr yeniləndi']);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Tənzimləmələr yenilənə bilmədi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
