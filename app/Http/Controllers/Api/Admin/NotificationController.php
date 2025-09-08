<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\NotificationTypeEnum;
use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\NotificationResource;
use App\Jobs\SendTelegramNotificationJob;
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
     * Admin üçün notification siyahısı
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
     * Tək notification göndərmək
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $validatedData = $this->validateRequest($request, $this->storeRules(), $this->storeMessages());

            try {
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
     * Notification detalları
     */
    public function show($id): JsonResponse
    {
        if ($this->authorizeAction('read')) {
            $notification = $this->service->findById($id);
            return response()->json([
                'data' => $this->toResource($notification)
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
     * Toplu notification göndərmək (seçilmiş user-lərə)
     *
     * @throws ValidationException
     */
    public function bulkSend(Request $request): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $validatedData = $this->validateRequest($request, [
                'user_ids' => 'required|array',
                'user_ids.*' => 'integer|exists:users,id',
                'type' => 'required|string|in:' . implode(',', NotificationTypeEnum::getValues()),
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'icon' => 'nullable|string',
                'action_url' => 'nullable|url',
                'action_text' => 'nullable|string|max:50',
                'priority' => 'nullable|string|in:low,normal,high',
                'send_at' => 'nullable|date|after:now'
            ]);

            try {
                $result = $this->service->sendBulk(
                    $validatedData['user_ids'],
                    $validatedData['type'],
                    $validatedData
                );

                return response()->json([
                    'message' => 'Kütləvi notification göndərildi',
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
     * Hamı user-lərə notification göndərmək
     *
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
                'priority' => 'nullable|string|in:low,normal,high',
                'send_at' => 'nullable|date|after:now',
                'exclude_user_ids' => 'nullable|array',
                'exclude_user_ids.*' => 'integer|exists:users,id'
            ]);

            try {
                $userQuery = User::query()->where('is_system', false);

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
                    'id' => $type,
                    'name' => NotificationTypeEnum::getDescription($type)
                ])->values()
            ];

            return response()->json($filters);
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Admin statistikalar
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
     * Test notification göndərmək
     *
     * @throws ValidationException
     */
    public function sendTest(Request $request): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $validatedData = $this->validateRequest($request, [
                'type' => 'required|string|in:' . implode(',', NotificationTypeEnum::getValues()),
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'test_email' => 'nullable|email',
            ]);

            try {
                // Admin özünə və ya test email-ə göndər
                $user = $validatedData['test_email']
                    ? User::where('email', $validatedData['test_email'])->first()
                    : Auth::user();

                if (!$user) {
                    return response()->json(['message' => 'Test user tapılmadı'], 404);
                }

                $notification = $this->service->send(
                    $user,
                    $validatedData['type'],
                    array_merge($validatedData, ['title' => '[TEST] ' . $validatedData['title']])
                );

                return response()->json([
                    'message' => 'Test notification göndərildi',
                    'data' => $this->toResource($notification)
                ], 201);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Test notification göndərmə xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * Telegram notification göndərmək
     *
     * @throws ValidationException
     */
    public function sendTelegram(Request $request): JsonResponse
    {
        if ($this->authorizeAction('create')) {
            $validatedData = $this->validateRequest($request, [
                'message' => 'required|string',
                'user_ids' => 'nullable|array',
                'user_ids.*' => 'integer|exists:users,id',
                'send_to_all' => 'nullable|boolean'
            ]);

            try {
                $userIds = null;

                if ($validatedData['send_to_all'] ?? false) {
                    // Hamıya göndər
                    $userIds = User::whereNotNull('telegram_id')->pluck('id')->toArray();
                } elseif (isset($validatedData['user_ids'])) {
                    // Seçilmiş user-lərə göndər
                    $userIds = $validatedData['user_ids'];
                }

                // Job-a göndər
                SendTelegramNotificationJob::dispatch(
                    $validatedData['message'],
                    $userIds
                );

                return response()->json([
                    'message' => 'Telegram notification queue-ya əlavə edildi',
                    'target_users' => $userIds ? count($userIds) : 'all'
                ], 201);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Telegram notification göndərmə xətası',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        return response()->json(['message' => $this->forbiddenMessage], 403);
    }

    /**
     * User axtarış (notification göndərmək üçün)
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
                ->where('is_system', false)
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
            'user_id' => 'required|integer|exists:users,id'
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
            'user_id.required' => 'İstifadəçi seçilməlidir',
            'user_id.exists' => 'İstifadəçi tapılmadı',
            'send_at.after' => 'Göndərmə tarixi gələcəkdə olmalıdır',
        ];
    }
}
