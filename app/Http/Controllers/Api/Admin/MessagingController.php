<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\MessageTypeEnum;
use App\Exceptions\BaseException;
use App\Http\Controllers\ApiController;
use App\Http\Resources\Admin\ConversationResource;
use App\Http\Resources\Admin\MessageResource;
use App\Services\Module\MessagingService;
use App\Traits\Controller\HasValidatesRequests;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class MessagingController extends ApiController
{
    use HasValidatesRequests;
    protected MessagingService $messagingService;

    public function __construct(MessagingService $messagingService)
    {
        parent::__construct($messagingService, 'messaging');
        $this->messagingService = $messagingService;
    }

    /**
     * Admin dashboard üçün mesajlaşma statistikalarını əldə edir
     */
    public function dashboard(): JsonResponse
    {
        if (!$this->authorizeAction('read')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        try {
            // Söhbət tiplərinə görə statistikalar
            $typeStats = $this->messagingService->getConversationTypeStatistics();

            // Status statistikaları
            $statusStats = $this->messagingService->getConversationStatusStatistics();

            // Son 30 gündəki söhbət statistikaları
            $dateStats = $this->messagingService->getConversationsByDateStatistics(30);

            // Ən aktiv istifadəçilər
            $activeUsers = $this->messagingService->getMostActiveUsers(10);

            return response()->json([
                'success' => true,
                'data' => [
                    'by_type' => $typeStats,
                    'by_status' => $statusStats,
                    'by_date' => $dateStats,
                    'most_active_users' => $activeUsers
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bütün söhbətləri listələyir - filter və səhifələmə ilə
     */
    public function getConversations(Request $request): JsonResponse
    {
        if (!$this->authorizeAction('read')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $filters = $request->all();
        $conversations = $this->messagingService->getAllConversations($filters);

        return response()->json([
            'data' => ConversationResource::collection($conversations),
            'total' => $conversations->total(),
        ]);
    }

    /**
     * UUID ilə söhbət detaylarını əldə edir
     */
    public function getConversation(string $uuid): JsonResponse
    {
        if (!$this->authorizeAction('read')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        try {
            $conversation = $this->messagingService->getConversationByUuid($uuid);

            return response()->json([
                'success' => true,
                'data' => new ConversationResource($conversation)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Söhbətə aid mesajları listələyir
     */
    public function getConversationMessages(Request $request, string $uuid): JsonResponse
    {
        if (!$this->authorizeAction('read')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        try {
            $conversation = $this->messagingService->getConversationByUuid($uuid);
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);

            $request->attributes->set('conversation', $conversation);

            $messages = $this->messagingService->getMessagesByConversationId($conversation->id, $page, $limit);

            return response()->json([
                'data' => MessageResource::collection($messages),
                'total' => $messages->total(),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Bütün mesajları listələyir - filter və səhifələmə ilə
     */
    public function getAllMessages(Request $request): JsonResponse
    {
        if (!$this->authorizeAction('read')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $filters = $request->all();
        $messages = $this->messagingService->getAllMessages($filters);

        return response()->json([
            'data' => MessageResource::collection($messages),
            'total' => $messages->total(),
        ]);
    }

    /**
     * Söhbəti blok edir
     * @throws Throwable
     */
    public function blockConversation(Request $request, string $uuid): JsonResponse
    {
        if (!$this->authorizeAction('update')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $this->validateRequest($request, [
            'reason' => 'nullable|string'
        ]);

        try {
            $conversation = $this->messagingService->blockConversation($uuid, $request->reason);

            return response()->json([
                'success' => true,
                'data' => new ConversationResource($conversation),
                'message' => 'Söhbət uğurla bloklandı'
            ]);

        } catch (BaseException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    /**
     * Söhbətin blokunu ləğv edir
     * @throws Throwable
     */
    public function unblockConversation(string $uuid): JsonResponse
    {
        if (!$this->authorizeAction('update')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        try {
            $conversation = $this->messagingService->unblockConversation($uuid);

            return response()->json([
                'success' => true,
                'data' => new ConversationResource($conversation),
                'message' => 'Söhbətin bloku uğurla ləğv edildi'
            ]);

        } catch (BaseException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    /**
     * Söhbətə sistem mesajı əlavə edir
     */
    public function sendSystemMessage(Request $request, string $uuid): JsonResponse
    {
        if (!$this->authorizeAction('create')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $this->validateRequest($request, [
            'content' => 'required|string'
        ]);

        $content = $request->get('content');

        try {
            $conversation = $this->messagingService->getConversationByUuid($uuid);

            $message = $this->messagingService->createSystemMessage(
                $conversation->id,
                $content
            );

            // Söhbətin son aktivliyini və meta məlumatlarını yeniləyirik
            $this->messagingService->updateLastActivity($uuid);
            $this->messagingService->updateMetaData($uuid, [
                'last_message' => [
                    'content' => mb_substr($content, 0, 50) . (mb_strlen($content) > 50 ? '...' : ''),
                    'sent_at' => now()->format('d.m.Y H:i'),
                    'sender_id' => Auth::id(),
                    'is_system' => true
                ]
            ]);

            return response()->json([
                'success' => true,
                'data' => new MessageResource($message),
                'message' => 'Sistem mesajı uğurla göndərildi'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mesajı silir (admins üçün)
     */
    public function deleteMessage(string $uuid): JsonResponse
    {
        if (!$this->authorizeAction('delete')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        try {
            $result = $this->messagingService->deleteMessage($uuid, true);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Mesaj uğurla silindi' : 'Mesaj silinərkən xəta baş verdi'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Bütün istifadəçilərə və ya seçilmiş istifadəçilərə eyni mesajı göndərir
     * @throws Throwable
     */
    public function sendBulkMessage(Request $request): JsonResponse
    {
        if (!$this->authorizeAction('create')) {
            return response()->json(['message' => $this->forbiddenMessage], 403);
        }

        $this->validateRequest($request, [
            'content' => 'required|string',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'type' => 'nullable|string|in:' . implode(',', MessageTypeEnum::getValues()),
            'filters' => 'nullable|array',
        ]);

        $content = $request->get('content');

        try {
            $result = $this->messagingService->sendBulkMessage(
                $content,
                $request->user_ids,
                $request->type ?? MessageTypeEnum::SYSTEM,
                $request->filters
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Mesaj uğurla göndərildi: {$result['success_count']} istifadəçiyə mesaj göndərildi, {$result['error_count']} xəta baş verdi."
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
