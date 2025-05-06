<?php

namespace App\Http\Controllers\Api\Front;

use App\Enums\ConversationTypeEnum;
use App\Enums\MessageTypeEnum;
use App\Exceptions\BaseException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Front\ConversationResource;
use App\Http\Resources\Front\MessageResource;
use App\Models\Listing;
use App\Services\Module\MessagingService;
use App\Services\Module\UserBlockService;
use App\Traits\Controller\HasValidatesRequests;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class MessagingController extends Controller
{
    use HasValidatesRequests;
    protected MessagingService $messagingService;
    protected UserBlockService $userBlockService;

    public function __construct(
        MessagingService $messagingService,
        UserBlockService $userBlockService
    )
    {
        $this->messagingService = $messagingService;
        $this->userBlockService = $userBlockService;
    }

    /**
     * İstifadəçilər arasında mesajlaşma imkanını yoxlayır
     * @throws BaseException
     */
    protected function checkUserBlockStatus(int $senderId, int $receiverId)
    {
        [$canMessage, $blockedBy] = $this->userBlockService->canMessage($senderId, $receiverId);

        if (!$canMessage) {
            if ($blockedBy === $senderId) {
                throw new BaseException(
                    'Siz bu istifadəçini bloklamısınız. Mesaj göndərmək üçün əvvəlcə bloku ləğv edin.',
                    409
                );
            } else {
                throw new BaseException(
                    'Bu istifadəçi sizi bloklamışdır və mesaj göndərə bilməzsiniz.',
                    409
                );
            }
        }
    }

    /**
     * İstifadəçinin söhbətlərini qaytarır
     */
    public function getConversations(): JsonResponse
    {
        $userId = Auth::id();
        $conversations = $this->messagingService->getUserConversations($userId);

        return response()->json(ConversationResource::collection($conversations));
    }

    /**
     * UUID ilə bir söhbəti qaytarır
     */
    public function getConversation(string $uuid): JsonResponse
    {
        $conversation = $this->messagingService->getConversationByUuid($uuid);

        // İstifadəçinin bu söhbətə icazəsi olub-olmadığını yoxlayırıq
        if ($conversation->creator_id !== Auth::id() && $conversation->receiver_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu söhbətə icazəniz yoxdur'
            ], 403);
        }

        // Mesajları oxundu kimi işarələyirik
        $this->messagingService->markConversationAsRead($conversation->id, Auth::id());

        // Söhbət məlumatlarını yeniləyirik
        $this->messagingService->updateLastActivity($uuid);

        return response()->json(new ConversationResource($conversation));
    }

    /**
     * UUID ilə söhbətə aid mesajları qaytarır (səhifələnmiş)
     */
    public function getMessages(Request $request, string $uuid): JsonResponse
    {
        $conversation = $this->messagingService->getConversationByUuid($uuid);

        // İstifadəçinin bu söhbətə icazəsi olub-olmadığını yoxlayır
        if ($conversation->creator_id !== Auth::id() && $conversation->receiver_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu söhbətə icazəniz yoxdur'
            ], 403);
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $messages = $this->messagingService->getMessagesByConversationId($conversation->id, $page, $limit);

        return response()->json([
            'data' => MessageResource::collection($messages),
            'total' => $messages->total()
        ]);
    }

    /**
     * Yeni mesaj göndərir
     * @throws BaseException
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $this->validateRequest($request, [
            'conversation_uuid' => 'required|string|exists:conversations,uuid',
            'content' => 'required_without:attachments|nullable|string',
            'attachments' => 'nullable|array',
            'type' => 'nullable|string|in:' . implode(',', MessageTypeEnum::getValues())
        ]);

        $conversation = $this->messagingService->getConversationByUuid($request->conversation_uuid);

        // İstifadəçinin bu söhbətə icazəsi olub-olmadığını yoxlayırıq
        if ($conversation->creator_id !== Auth::id() && $conversation->receiver_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu söhbətə mesaj göndərmək icazəniz yoxdur'
            ], 403);
        }

        // Söhbət bloklanıbsa mesaj göndərməyə icazə vermirik
        if ($conversation->status === 'blocked') {
            return response()->json([
                'success' => false,
                'message' => 'Bu söhbət bloklanıb və mesaj göndərilə bilməz'
            ], 403);
        }

        $receiverId = $conversation->creator_id === Auth::id()
            ? $conversation->receiver_id
            : $conversation->creator_id;

        // Blok vəziyyətini yoxlayırıq
        $this->checkUserBlockStatus(Auth::id(), $receiverId);

        $content = $request->input('content');

        $message = $this->messagingService->createMessage(
            $conversation->id,
            Auth::id(),
            $content,
            $request->type ?? MessageTypeEnum::TEXT,
            $request->attachments
        );

        // Söhbətin son aktivliyini və meta məlumatlarını yeniləyirik
        $this->messagingService->updateLastActivity($request->conversation_uuid);
        $this->messagingService->updateMetaData($request->conversation_uuid, [
            'last_message' => [
                'content' => $content ? mb_substr($content, 0, 50) . (mb_strlen($content) > 50 ? '...' : '') : 'Fayl göndərildi',
                'sent_at' => now()->format('d.m.Y H:i'),
                'sender_id' => Auth::id()
            ]
        ]);

        return response()->json([
            'success' => true,
            'data' => new MessageResource($message)
        ]);
    }

    /**
     * Yeni söhbət yaradır
     * @throws BaseException
     */
    public function createConversation(Request $request)
    {
        $this->validateRequest($request, [
            'receiver_id' => 'required|integer|exists:users,id',
            'content' => 'required|string',
            'type' => 'nullable|string|in:' . implode(',', ConversationTypeEnum::getValues()),
        ]);

        $content = $request->input('content');
        $conversationable_type = null;

        $cType = Relation::morphMap([
            'listing' => Listing::class,
        ]);

        if (Arr::has($cType, $request->type)) {
            $conversationable_type = $cType[$request->type];
            $conversationable_id = $request->conversationable_id;
        }
        else {
            $conversationable_id = null;
        }

        // Özünə mesaj göndərməməlidir
        if ($request->receiver_id == Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Özünüzə mesaj göndərə bilməzsiniz'
            ], 422);
        }

        $this->checkUserBlockStatus(Auth::id(), $request->receiver_id);

        // Yeni söhbət yaradırıq
        $conversation = $this->messagingService->createConversation(
            Auth::id(),
            $request->receiver_id,
            $conversationable_type,
            $conversationable_id,
            $request->type
        );

        // İlk mesajı göndəririk
        $message = $this->messagingService->createMessage(
            $conversation->id,
            Auth::id(),
            $content,
            MessageTypeEnum::TEXT
        );

        // Söhbətin meta məlumatlarını yeniləyirik
        $this->messagingService->updateMetaData($conversation->uuid, [
            'last_message' => [
                'content' => mb_substr($content, 0, 50) . (mb_strlen($content) > 50 ? '...' : ''),
                'sent_at' => now()->format('d.m.Y H:i'),
                'sender_id' => Auth::id()
            ]
        ]);

        return response()->json(new ConversationResource($conversation));
    }

    /**
     * Söhbətin pin statusunu dəyişir
     */
    public function togglePin(string $uuid): JsonResponse
    {
        $conversation = $this->messagingService->getConversationByUuid($uuid);

        // İstifadəçinin bu söhbətə icazəsi olub-olmadığını yoxlayırıq
        if ($conversation->creator_id !== Auth::id() && $conversation->receiver_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu söhbəti pinləmək icazəniz yoxdur'
            ], 403);
        }

        $conversation = $this->messagingService->togglePin($uuid);

        return response()->json([
            'success' => true,
            'data' => new ConversationResource($conversation),
            'message' => $conversation->is_pinned ? 'Söhbət pinləndi' : 'Söhbət pin-dən çıxarıldı'
        ]);
    }

    /**
     * Söhbəti arxivləşdirir
     */
    public function archiveConversation(string $uuid): JsonResponse
    {
        $conversation = $this->messagingService->getConversationByUuid($uuid);

        // İstifadəçinin bu söhbətə icazəsi olub-olmadığını yoxlayırıq
        if ($conversation->creator_id !== Auth::id() && $conversation->receiver_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu söhbəti arxivləşdirmək icazəniz yoxdur'
            ], 403);
        }

        $conversation = $this->messagingService->archiveConversation($uuid);

        return response()->json([
            'success' => true,
            'message' => 'Söhbət arxivləşdirildi'
        ]);
    }

    /**
     * Söhbəti arxivdən çıxarır
     */
    public function unArchiveConversation(string $uuid): JsonResponse
    {
        $conversation = $this->messagingService->getConversationByUuid($uuid);

        // İstifadəçinin bu söhbətə icazəsi olub-olmadığını yoxlayırıq
        if ($conversation->creator_id !== Auth::id() && $conversation->receiver_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu söhbəti arxivdən çıxarmaq icazəniz yoxdur'
            ], 403);
        }

        $conversation = $this->messagingService->unArchiveConversation($uuid);

        return response()->json([
            'success' => true,
            'message' => 'Söhbət arxivdən çıxarıldı'
        ]);
    }

    /**
     * Mesajları oxundu kimi işarələyir
     */
    public function markMessagesAsRead(Request $request): JsonResponse
    {
        $this->validateRequest($request, [
            'message_uuids' => 'required|array',
            'message_uuids.*' => 'required|string|exists:messages,uuid'
        ]);

        $this->messagingService->markMultipleAsRead($request->message_uuids);

        return response()->json([
            'success' => true,
            'message' => 'Mesajlar oxundu kimi işarələndi'
        ]);
    }

    /**
     * İstifadəçilər arasında axtarış aparır (yeni söhbət başlatmaq üçün)
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $users = $this->messagingService->searchUsersForNewConversation($request->search, $limit);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * İstifadəçinin oxunmamış mesaj sayını qaytarır
     */
    public function getUnreadCount(): JsonResponse
    {
        $userId = Auth::id();
        $conversations = $this->messagingService->getUserUnreadConversations($userId);

        $totalUnread = $this->messagingService->getUserUnreadMessageCount($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $totalUnread,
                'unread_conversations' => $conversations->count()
            ]
        ]);
    }
}
