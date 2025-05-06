<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\NotificationResource;
use App\Services\Module\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function __construct(NotificationService $service)
    {
        parent::__construct($service, 'notification');
        $this->setResource(NotificationResource::class);
    }

    /**
     * Admin panel üçün notification siyahısı və statistika
     * Bu metod admin paneldə bütün bildirişləri və onların statistikasını göstərir
     */
    public function index(): JsonResponse
    {
        if (!$this->authorizeAction('read')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $data = $this->service->paginateAndFilter();

        return response()->json([
            'data' => $this->toResource($data),
            'total' => $data->total(),
            'stats' => $this->service->getAdminStats()
        ]);
    }

    /**
     * Admin tərəfindən sistem bildirişi yaratmaq
     * Bu metod vasitəsilə admin seçilmiş istifadəçilərə bildiriş göndərə bilər
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->authorizeAction('create')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'send_at' => 'nullable|date|after:now',
            'action_url' => 'nullable|url'
        ]);

        $notifications = $this->service->createSystemNotification($request->all());

        return response()->json([
            'message' => 'Sistem bildirişi uğurla yaradıldı',
            'data' => NotificationResource::collection($notifications)
        ], 201);
    }

    /**
     * Admin tərəfindən bildirişi silmək
     * Bu metod vasitəsilə admin artıq lazım olmayan bildirişləri sistemdən silə bilər
     */
    public function destroy(mixed $id): JsonResponse
    {
        if (!$this->authorizeAction('delete')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $this->service->delete($id);

        return response()->json([
            'message' => 'Bildiriş uğurla silindi'
        ]);
    }

    /**
     * Admin tərəfindən çoxlu bildiriş silmək
     * Bu metod vasitəsilə admin bir neçə bildirişi eyni anda silə bilər
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        if (!$this->authorizeAction('delete')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:notifications,id'
        ]);

        $this->service->deleteMultiple($request->ids);

        return response()->json([
            'message' => 'Seçilmiş bildirişlər uğurla silindi'
        ]);
    }

    /**
     * İstifadəçinin notification siyahısı
     * Bu metod istifadəçinin özünə aid olan bildirişləri qaytarır
     */
    public function userNotifications(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:read,unread,all',
            'per_page' => 'nullable|integer|min:1|max:50'
        ]);

        $notifications = $this->service->getUserNotifications(
            auth()->user(),
            $request->status ?? 'all',
            $request->per_page ?? 15
        );

        return response()->json([
            'data' => NotificationResource::collection($notifications->items()),
            'total' => $notifications->total(),
            'unread_count' => auth()->user()->unreadNotifications()->count()
        ]);
    }

    /**
     * Bildirişi oxunmuş kimi işarələmək
     * İstifadəçi bu metod vasitəsilə bildirişi oxunmuş kimi işarələyə bilər
     */
    public function markAsRead(int $id): JsonResponse
    {
        $notification = $this->service->findById($id);

        if ($notification->notifiable_id !== auth()->id()) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $this->service->markAsRead($notification);

        return response()->json([
            'message' => 'Bildiriş oxunmuş kimi işarələndi',
            'unread_count' => auth()->user()->unreadNotifications()->count()
        ]);
    }

    /**
     * Bütün bildirişləri oxunmuş kimi işarələmək
     * İstifadəçi bu metod vasitəsilə bütün bildirişlərini oxunmuş kimi işarələyə bilər
     */
    public function markAllAsRead(): JsonResponse
    {
        $this->service->markAllAsRead(auth()->user());

        return response()->json([
            'message' => 'Bütün bildirişlər oxunmuş kimi işarələndi',
            'unread_count' => 0
        ]);
    }

    /**
     * İstifadəçinin bildiriş statistikası
     * Bu metod istifadəçinin bildirişləri haqqında ümumi statistikanı qaytarır
     */
    public function userStats(): JsonResponse
    {
        $stats = $this->service->getUserStats(auth()->user());

        return response()->json($stats);
    }
}
