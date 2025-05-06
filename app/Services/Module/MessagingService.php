<?php

namespace App\Services\Module;

use App\Enums\ConversationStatusEnum;
use App\Enums\ConversationTypeEnum;
use App\Enums\MessageStatusEnum;
use App\Enums\MessageTypeEnum;
use App\Exceptions\BaseException;
use App\Models\Conversation;
use App\Models\Message;
use App\Repositories\Interface\BaseRepositoryInterface;
use App\Repositories\Module\MessagingRepository;
use App\Services\BaseCrudService;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class MessagingService extends BaseCrudService
{
    protected BaseRepositoryInterface $repository;

    public function __construct(MessagingRepository $repository)
    {
        parent::__construct($repository);
    }

    //===================================//
    // CONVERSATION RELATED METHODS
    //===================================//

    /**
     * Bütün söhbətləri filtrlə və səhifələmə ilə əldə edir
     */
    public function getAllConversations(array $filters = []): LengthAwarePaginator
    {
        return $this->repository->getAllConversationsWithFilters($filters);
    }

    /**
     * İstifadəçinin bütün söhbətlərini əldə edir
     */
    public function getUserConversations(int $userId, ?string $status = null): Collection
    {
        return $this->repository->getUserConversations($userId, $status);
    }

    /**
     * İstifadəçinin oxunmamış mesaj olan söhbətlərini əldə edir
     */
    public function getUserUnreadConversations(int $userId): Collection
    {
        return $this->repository->getUserUnreadConversations($userId);
    }

    /**
     * UUID ilə söhbəti əldə edir
     */
    public function getConversationByUuid(string $uuid): Conversation
    {
        return $this->repository->findConversationByUuid($uuid);
    }

    /**
     * Yeni söhbət yaradır
     */
    public function createConversation(
        int $creatorId,
        int $receiverId,
        ?string $conversationableType = null,
        ?int $conversationableId = null,
        ?string $type = null
    ): Conversation {
        // Mövcud söhbət varmı yoxlayırıq
        $existingConversation = $this->repository->findExistingConversation(
            $creatorId,
            $receiverId,
            $conversationableType,
            $conversationableId
        );

        if ($existingConversation) {
            $existingConversation->update([
                'last_activity_at' => now()
            ]);
            return $existingConversation;
        }

        // Söhbət məlumatlarını hazırlayırıq
        $conversationData = [
            'creator_id' => $creatorId,
            'receiver_id' => $receiverId,
            'type' => $type ?? ConversationTypeEnum::PRIVATE,
            'status' => ConversationStatusEnum::ACTIVE,
            'last_activity_at' => now()
        ];

        // Elan və ya başqa obyektlə əlaqəsini əlavə edirik
        if ($conversationableType && $conversationableId) {
            $conversationData['conversationable_type'] = $conversationableType;
            $conversationData['conversationable_id'] = $conversationableId;
        }

        return $this->repository->createConversation($conversationData);
    }

    /**
     * Söhbətin pin statusunu dəyişdirir
     */
    public function togglePin(string $uuid): Conversation
    {
        $conversation = $this->getConversationByUuid($uuid);
        $conversation->togglePin();
        return $conversation;
    }

    /**
     * Söhbəti arxivləşdirir
     */
    public function archiveConversation(string $uuid): Conversation
    {
        $conversation = $this->getConversationByUuid($uuid);
        $conversation->markAsArchived();
        return $conversation;
    }

    /**
     * Söhbəti arxivdən çıxarır
     */
    public function unArchiveConversation(string $uuid): Conversation
    {
        $conversation = $this->getConversationByUuid($uuid);
        $conversation->markAsActive();
        return $conversation;
    }

    /**
     * Söhbəti bloklayır (Admin funksiyası)
     * @throws Throwable
     */
    public function blockConversation(string $uuid, ?string $reason = null): Conversation
    {
        $conversation = $this->getConversationByUuid($uuid);

        // Yalnız admin bloklama hüququna sahibdir
        if (!Auth::user()->hasRole('admin')) {
            throw new BaseException('Bu əməliyyatı yerinə yetirmək üçün hüququnuz yoxdur');
        }

        DB::beginTransaction();

        try {
            $conversation->update([
                'status' => ConversationStatusEnum::BLOCKED
            ]);

            // Bloklama səbəbini əlavə edirik
            if ($reason) {
                $metaData = $conversation->meta_data ?? [];
                $metaData['blocked_reason'] = $reason;
                $metaData['blocked_at'] = now()->toDateTimeString();
                $metaData['blocked_by'] = Auth::id();

                $conversation->update([
                    'meta_data' => $metaData
                ]);
            }

            // Sistem mesajı əlavə edirik
            $this->createSystemMessage(
                $conversation->id,
                'Bu söhbət admin tərəfindən bloklanmışdır.' . ($reason ? " Səbəb: {$reason}" : '')
            );

            DB::commit();

            return $conversation;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Söhbətin blokunu açır (Admin funksiyası)
     * @throws BaseException|Throwable
     */
    public function unblockConversation(string $uuid): Conversation
    {
        $conversation = $this->getConversationByUuid($uuid);

        // Yalnız admin bloklama hüququna sahibdir
        if (!Auth::user()->hasRole('admin')) {
            throw new BaseException('Bu əməliyyatı yerinə yetirmək üçün hüququnuz yoxdur');
        }

        DB::beginTransaction();

        try {
            $conversation->update([
                'status' => ConversationStatusEnum::ACTIVE
            ]);

            // Blokdan çıxarma məlumatını əlavə edirik
            $metaData = $conversation->meta_data ?? [];
            $metaData['unblocked_at'] = now()->toDateTimeString();
            $metaData['unblocked_by'] = Auth::id();

            $conversation->update([
                'meta_data' => $metaData
            ]);

            // Sistem mesajı əlavə edirik
            $this->createSystemMessage(
                $conversation->id,
                'Bu söhbət admin tərəfindən blokdan çıxarılmışdır.'
            );

            DB::commit();

            return $conversation;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Söhbətin son aktivliyini yeniləyir
     */
    public function updateLastActivity(string $uuid): Conversation
    {
        $conversation = $this->getConversationByUuid($uuid);
        $conversation->updateLastActivity();
        return $conversation;
    }

    /**
     * Söhbətin meta məlumatlarını yeniləyir
     */
    public function updateMetaData(string $uuid, array $data): Conversation
    {
        $conversation = $this->getConversationByUuid($uuid);
        $conversation->updateMetaData($data);
        return $conversation;
    }

    //===================================//
    // MESSAGE RELATED METHODS
    //===================================//

    /**
     * Mesaj yaradır
     */
    public function createMessage(
        int $conversationId,
        int $senderId,
        ?string $content = null,
        string $type = MessageTypeEnum::TEXT,
        ?array $attachments = null,
    ): Message {
        $data = [
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'content' => $content,
            'type' => $type,
            'attachments' => $attachments,
            'status' => MessageStatusEnum::SENT,
        ];

        return $this->repository->createMessage($data);
    }

    /**
     * Sistem mesajı yaradır
     */
    public function createSystemMessage(
        int $conversationId,
        string $content,
    ): Message {
        $data = [
            'conversation_id' => $conversationId,
            'sender_id' => 1, // System user
            'type' => MessageTypeEnum::SYSTEM,
            'content' => $content,
            'status' => MessageStatusEnum::SENT,
            'is_system' => true,
        ];

        return $this->repository->createMessage($data);
    }

    /**
     * Mesajları söhbət ID-sinə görə əldə edir
     */
    public function getMessagesByConversationId(int $conversationId, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        return $this->repository->getMessagesByConversationId($conversationId, $page, $limit);
    }

    /**
     * UUID ilə mesajı tapır
     */
    public function getMessageByUuid(string $uuid): Message
    {
        return $this->repository->findMessageByUuid($uuid);
    }

    /**
     * Bütün sistemdəki mesajları əldə edir (Admin üçün)
     */
    public function getAllMessages(array $filters = []): LengthAwarePaginator
    {
        return $this->repository->getAllMessagesWithFilters($filters);
    }

    /**
     * Mesajı oxunmuş kimi işarələyir
     */
    public function markMessageAsRead(string $messageUuid): Message
    {
        return $this->repository->markMessageAsRead($messageUuid);
    }

    /**
     * Çoxlu mesajları oxunmuş kimi işarələyir
     */
    public function markMultipleAsRead(array $messageUuids): void
    {
        $this->repository->markMultipleAsRead($messageUuids);
    }

    /**
     * Söhbətdəki bütün mesajları oxunmuş kimi işarələyir
     */
    public function markConversationAsRead(int $conversationId, int $userId): void
    {
        $this->repository->markConversationAsRead($conversationId, $userId);
    }

    /**
     * Mesajı redaktə edir
     * @throws Exception|Throwable
     */
    public function editMessage(string $messageUuid, string $newContent): Message
    {
        $message = $this->getMessageByUuid($messageUuid);

        // Yalnız mesajı göndərən redaktə edə bilər
        if ($message->sender_id !== Auth::id()) {
            throw new BaseException('Bu mesajı redaktə etmək hüququnuz yoxdur');
        }

        // Sistemin icazə verdiyi müddət ərzində redaktə edilə bilər (30 dəqiqə)
        if (now()->diffInMinutes($message->created_at) > 30) {
            throw new BaseException('Mesaj artıq redaktə edilə bilməz (30 dəqiqəlik müddət keçib)');
        }

        DB::beginTransaction();

        try {
            // Redaktə tarixçəsi üçün meta_data hazırlayırıq
            $metaData = $message->meta_data ?? [];

            if (!isset($metaData['edit_history'])) {
                $metaData['edit_history'] = [];
            }

            // Köhnə məzmunu tarixçəyə əlavə edirik
            $metaData['edit_history'][] = [
                'content' => $message->content,
                'edited_at' => now()->toDateTimeString(),
                'edited_by' => Auth::id()
            ];

            // Mesajı yeniləyirik
            $message->update([
                'content' => $newContent,
                'meta_data' => $metaData,
                'is_edited' => true,
                'edited_at' => now()
            ]);

            DB::commit();

            return $message;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mesajı silir
     * @throws Exception
     */
    public function deleteMessage(string $messageUuid, bool $forEveryone = false): bool
    {
        $message = $this->getMessageByUuid($messageUuid);

        // Yalnız mesajı göndərən və ya admin silə bilər
        if ($message->sender_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            throw new Exception('Bu mesajı silmək hüququnuz yoxdur');
        }

        if ($forEveryone) {
            // Hamı üçün silmə - tam silinmə
            return $this->repository->deleteMessage($message->id);
        } else {
            // Yalnız özün üçün silmə - meta_data-ya qeyd edilir
            $metaData = $message->meta_data ?? [];

            if (!isset($metaData['deleted_for'])) {
                $metaData['deleted_for'] = [];
            }

            $metaData['deleted_for'][] = Auth::id();

            $message->update([
                'meta_data' => $metaData
            ]);

            return true;
        }
    }

    /**
     * İstifadəçilər arasında axtarış aparır (yeni söhbət başlatmaq üçün)
     */
    public function searchUsersForNewConversation(string $search, int $limit = 10): Collection
    {
        return $this->repository->searchUsers($search, $limit);
    }

    //===================================//
    // STATISTICS & REPORTS
    //===================================//

    /**
     * Mesaj statistikası əldə edir
     */
    public function getMessageStatistics(int $conversationId): array
    {
        return $this->repository->getMessageStatistics($conversationId);
    }

    /**
     * Konversasiya tipinə görə statistika
     */
    public function getConversationTypeStatistics(): array
    {
        return $this->repository->getConversationTypeStatistics();
    }

    /**
     * Statuslara görə statistika
     */
    public function getConversationStatusStatistics(): array
    {
        return $this->repository->getConversationStatusStatistics();
    }

    /**
     * Zaman aralığına görə yaradılmış söhbətlər statistikası
     */
    public function getConversationsByDateStatistics(int $days = 30): array
    {
        return $this->repository->getConversationsByDateStatistics($days);
    }

    /**
     * Ən çox söhbət edən istifadəçilər
     */
    public function getMostActiveUsers(int $limit = 10): array
    {
        return $this->repository->getMostActiveUsers($limit);
    }

    /**
     * İstifadəçinin oxunmamış mesaj sayını əldə edir
     */
    public function getUserUnreadMessageCount(int $userId): int
    {
        return $this->repository->getUserUnreadMessageCount($userId);
    }

    /**
     * Bütün istifadəçilərə və ya seçilmiş istifadəçilərə eyni mesajı göndərir
     *
     * @param string $content Mesaj məzmunu
     * @param array|null $userIds İstifadəçi ID-ləri (null olduqda bütün aktiv istifadəçilərə göndərilir)
     * @param string $type Mesaj tipi (mətn, sistem, vs.)
     * @param array|null $filters İstifadəçi filtrləri (əgər userIds null olarsa istifadə edilir)
     *
     * @return array ['conversation_count' => int, 'success_count' => int, 'error_count' => int]
     * @throws Throwable
     */
    public function sendBulkMessage(
        string $content,
        ?array $userIds = null,
        string $type = MessageTypeEnum::SYSTEM,
        ?array $filters = null
    ): array
    {
        // Əməliyyat nəticələrini saxlamaq üçün dəyişənlər
        $conversationCount = 0;
        $successCount = 0;
        $errorCount = 0;
        $adminId = Auth::id();

        // Əgər xüsusi istifadəçi ID-ləri təyin edilməyibsə və filtrlər verilibsə
        if ($userIds === null && $filters !== null) {
            $userIds = $this->repository->getUserIdsByFilters($filters);
        }
        // Əgər heç bir ID və filtr yoxdursa, bütün aktiv istifadəçiləri əldə edirik
        elseif ($userIds === null) {
            $userIds = $this->repository->getAllActiveUserIds();
        }

        DB::beginTransaction();

        try {
            // Hər bir istifadəçi üçün söhbət yaradırıq (əgər yoxdursa) və mesaj göndəririk
            foreach ($userIds as $userId) {
                // Özü-özünə mesaj göndərməməlidir
                if ($userId == $adminId) {
                    continue;
                }

                // Mövcud sistemli söhbəti tapırıq və ya yenisini yaradırıq
                $conversation = $this->repository->findExistingConversation(
                    $adminId,
                    $userId,
                    null,
                    null,
                    ConversationTypeEnum::SYSTEM
                );

                if (!$conversation) {
                    $conversation = $this->createConversation(
                        $adminId,
                        $userId,
                        null,
                        null,
                        ConversationTypeEnum::SYSTEM
                    );
                    $conversationCount++;
                }

                try {
                    // Mesajı yaradırıq
                    $message = $this->createMessage(
                        $conversation->id,
                        $adminId,
                        $content,
                        $type
                    );

                    // Söhbətin son aktivliyini və meta məlumatlarını yeniləyirik
                    $this->updateLastActivity($conversation->uuid);
                    $this->updateMetaData($conversation->uuid, [
                        'last_message' => [
                            'content' => mb_substr($content, 0, 50) . (mb_strlen($content) > 50 ? '...' : ''),
                            'sent_at' => now()->format('d.m.Y H:i'),
                            'sender_id' => $adminId,
                            'is_system' => $type === MessageTypeEnum::SYSTEM
                        ]
                    ]);

                    $successCount++;
                } catch (Exception $e) {
                    // Xəta baş verdikdə qeydə alırıq və növbəti istifadəçiyə keçirik
                    $errorCount++;
                    report($e);
                    continue;
                }
            }

            DB::commit();

            return [
                'conversation_count' => $conversationCount,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'total_users' => count($userIds)
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
