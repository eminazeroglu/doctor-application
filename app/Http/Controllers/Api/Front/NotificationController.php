<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\NotificationResource;
use App\Services\Module\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public NotificationService $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
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


