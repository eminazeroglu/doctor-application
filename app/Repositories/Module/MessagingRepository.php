<?php

namespace App\Repositories\Module;

use App\Enums\ConversationStatusEnum;
use App\Enums\MessageStatusEnum;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Repositories\BaseRepository;
use App\Services\Filter\MessagingFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MessagingRepository extends BaseRepository
{
    protected Message $messageModel;

    public function __construct(Conversation $conversationModel, Message $messageModel)
    {
        parent::__construct($conversationModel);
        $this->messageModel = $messageModel;
        $this->with = ['lastMessage', 'creator', 'receiver'];
        $this->setFilter(new MessagingFilter(request()));
    }

    //===================================//
    // CONVERSATION RELATED METHODS
    //===================================//

    /**
     * İstifadəçinin bütün söhbətlərini əldə edir
     */
    public function getUserConversations(int $userId, ?string $status = null): Collection
    {
        $query = $this->model->newQuery()
            ->where(function($query) use ($userId) {
                $query->where('creator_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->with(['lastMessage', 'creator', 'receiver']);

        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where('status', ConversationStatusEnum::ACTIVE);
        }

        return $query->orderBy('is_pinned', 'desc')
            ->orderBy('last_activity_at', 'desc')
            ->get();
    }

    /**
     * İstifadəçinin oxunmamış mesaj olan söhbətlərini əldə edir
     */
    public function getUserUnreadConversations(int $userId): Collection
    {
        return $this->model->newQuery()
            ->where(function($query) use ($userId) {
                $query->where('creator_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->where('status', ConversationStatusEnum::ACTIVE)
            ->whereHas('messages', function($query) use ($userId) {
                $query->where('sender_id', '!=', $userId)
                    ->whereNull('read_at');
            })
            ->with(['lastMessage', 'creator', 'receiver'])
            ->orderBy('last_activity_at', 'desc')
            ->get();
    }

    /**
     * UUID ilə söhbəti tapır
     */
    public function findConversationByUuid(string $uuid): Conversation
    {
        return $this->model->newQuery()
            ->where('uuid', $uuid)
            ->with(['lastMessage', 'creator', 'receiver'])
            ->firstOrFail();
    }

    /**
     * İki istifadəçi arasında mövcud söhbəti tapır (və ya null qaytarır)
     */
    public function findExistingConversation(
        int $userId1,
        int $userId2,
        ?string $conversationableType = null,
        ?int $conversationableId = null
    ): ?Conversation
    {
        $query = $this->model->newQuery()
            ->where(function($query) use ($userId1, $userId2) {
                $query->where(function($q) use ($userId1, $userId2) {
                    $q->where('creator_id', $userId1)
                        ->where('receiver_id', $userId2);
                })
                    ->orWhere(function($q) use ($userId1, $userId2) {
                        $q->where('creator_id', $userId2)
                            ->where('receiver_id', $userId1);
                    });
            })
            ->where('status', ConversationStatusEnum::ACTIVE);

        if ($conversationableType && $conversationableId) {
            $query->where('conversationable_type', $conversationableType)
                ->where('conversationable_id', $conversationableId);
        } else {
            $query->whereNull('conversationable_type')
                ->whereNull('conversationable_id');
        }

        return $query->first();
    }

    /**
     * Söhbət yaradır
     */
    public function createConversation(array $data): Conversation
    {
        return $this->model->create($data);
    }

    /**
     * Bütün söhbətləri filter və səhifələmə ilə əldə edir (Admin Panel üçün)
     */
    public function getAllConversationsWithFilters(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with([
                'lastMessage',
                'creator',
                'receiver'
            ]);

        // Filterlər
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['user_id'])) {
            $query->where(function($q) use ($filters) {
                $q->where('creator_id', $filters['user_id'])
                    ->orWhere('receiver_id', $filters['user_id']);
            });
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('creator', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })->orWhereHas('receiver', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('last_activity_at', 'desc')
            ->paginate($filters['limit'] ?? 15);
    }

    //===================================//
    // MESSAGE RELATED METHODS
    //===================================//

    /**
     * Mesaj yaradır
     */
    public function createMessage(array $data): Message
    {
        return $this->messageModel->create($data);
    }

    /**
     * UUID ilə mesajı tapır
     */
    public function findMessageByUuid(string $uuid): Message
    {
        return $this->messageModel->where('uuid', $uuid)->firstOrFail();
    }

    /**
     * Mesajları söhbət ID-sinə görə səhifələnmiş şəkildə əldə edir
     */
    public function getMessagesByConversationId(int $conversationId, int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        return $this->messageModel
            ->where('conversation_id', $conversationId)
            ->with(['sender'])
            ->orderBy('created_at', 'desc') // Ən yeni mesajlar əvvəlcə
            ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Mesajı oxundu kimi işarələyir
     */
    public function markMessageAsRead(string $messageUuid): Message
    {
        $message = $this->findMessageByUuid($messageUuid);
        $message->markAsRead();
        return $message;
    }

    /**
     * Çoxlu mesajları oxunmuş kimi işarələyir
     */
    public function markMultipleAsRead(array $messageUuids): void
    {
        $this->messageModel->whereIn('uuid', $messageUuids)
            ->whereNull('read_at')
            ->update([
                'status' => MessageStatusEnum::READ,
                'read_at' => now()
            ]);
    }

    /**
     * Söhbətdəki oxunmamış mesajları oxundu kimi işarələyir
     */
    public function markConversationAsRead(int $conversationId, int $userId): void
    {
        $this->messageModel->where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId) // İstifadəçinin öz göndərdiyi mesajlar deyil
            ->whereNull('read_at')
            ->update([
                'status' => MessageStatusEnum::READ,
                'read_at' => now()
            ]);
    }

    /**
     * Mesajı silir
     */
    public function deleteMessage(int $messageId): bool
    {
        return $this->messageModel->where('id', $messageId)->delete();
    }

    /**
     * Bütün mesajları filter və səhifələmə ilə əldə edir (Admin Panel üçün)
     */
    public function getAllMessagesWithFilters(array $filters = []): LengthAwarePaginator
    {
        $query = $this->messageModel->query()
            ->with(['sender', 'conversation']);

        // Filterlər
        if (isset($filters['conversation_id'])) {
            $query->where('conversation_id', $filters['conversation_id']);
        }

        if (isset($filters['sender_id'])) {
            $query->where('sender_id', $filters['sender_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_system'])) {
            $query->where('is_system', $filters['is_system']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $query->where('content', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['limit'] ?? 15);
    }

    //===================================//
    // STATISTICS & REPORTS
    //===================================//

    /**
     * Mesaj statistikası əldə edir
     */
    public function getMessageStatistics(int $conversationId): array
    {
        $totalCount = $this->messageModel->where('conversation_id', $conversationId)->count();
        $readCount = $this->messageModel->where('conversation_id', $conversationId)
            ->whereNotNull('read_at')->count();
        $unreadCount = $totalCount - $readCount;

        $messagesByDate = $this->getMessageCountByDate($conversationId);

        return [
            'total_messages' => $totalCount,
            'read_messages' => $readCount,
            'unread_messages' => $unreadCount,
            'messages_by_date' => $messagesByDate
        ];
    }

    /**
     * Günlər üzrə mesaj sayı statistikasını əldə edir
     */
    public function getMessageCountByDate(int $conversationId, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $messages = $this->messageModel
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('conversation_id', $conversationId)
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $result = [];
        foreach ($messages as $item) {
            $result[$item->date] = $item->count;
        }

        return $result;
    }

    /**
     * İstifadəçinin oxunmamış mesajları və sayını əldə edir
     */
    public function getUserUnreadMessageCount(int $userId): int
    {
        return $this->messageModel
            ->whereHas('conversation', function($query) use ($userId) {
                $query->where(function($q) use ($userId) {
                    $q->where('creator_id', $userId)
                        ->orWhere('receiver_id', $userId);
                })
                    ->where('status', ConversationStatusEnum::ACTIVE);
            })
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Konversasiya tipinə görə statistika
     */
    public function getConversationTypeStatistics(): array
    {
        return $this->model->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Statuslara görə statistika
     */
    public function getConversationStatusStatistics(): array
    {
        return $this->model->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Zaman aralığına görə yaradılmış söhbətlər statistikası
     */
    public function getConversationsByDateStatistics(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $conversations = $this->model->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $result = [];
        foreach ($conversations as $item) {
            $result[$item->date] = $item->count;
        }

        return $result;
    }

    /**
     * Ən çox söhbət edən istifadəçilər
     */
    public function getMostActiveUsers(int $limit = 10): array
    {
        $creatorStats = $this->model->select('creator_id as user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('creator_id')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();

        $receiverStats = $this->model->select('receiver_id as user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('receiver_id')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();

        // İki sorğu nəticəsini birləşdiririk
        $combined = [];
        foreach ($creatorStats as $stat) {
            $userId = $stat->user_id;
            $combined[$userId] = ($combined[$userId] ?? 0) + $stat->count;
        }

        foreach ($receiverStats as $stat) {
            $userId = $stat->user_id;
            $combined[$userId] = ($combined[$userId] ?? 0) + $stat->count;
        }

        // Nəticəni sayına görə sıralayırıq
        arsort($combined);

        // Limiti tətbiq edirik
        return array_slice($combined, 0, $limit, true);
    }

    /**
     * İstifadəçilər arasında axtarış aparır (yeni söhbət başlatmaq üçün)
     */
    public function searchUsers(string $search, int $limit = 10): Collection
    {
        return app(UserRepository::class)->searchUsers($search, $limit);
    }

    /**
     * Filtrlərə əsasən istifadəçi ID-lərini əldə edir
     *
     * @param array $filters İstifadəçi filtrləri
     * @return array İstifadəçi ID-ləri
     */
    public function getUserIdsByFilters(array $filters): array
    {
        $query = User::query()->select('id');

        // Status filtrləri
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', 'active'); // Default olaraq aktiv istifadəçilər
        }

        // Role filtrləri
        if (isset($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        // Qeydiyyat tarixi filtrləri
        if (isset($filters['registered_from'])) {
            $query->where('created_at', '>=', $filters['registered_from']);
        }

        if (isset($filters['registered_to'])) {
            $query->where('created_at', '<=', $filters['registered_to']);
        }

        // Admin olmayan istifadəçilər
        if (isset($filters['exclude_admins']) && $filters['exclude_admins']) {
            $query->whereHas('role', function($q) {
                $q->where('group_name', '!=', 'admin');
            });
        }

        // Yalnız email təsdiqli istifadəçilər
        if (isset($filters['verified_only']) && $filters['verified_only']) {
            $query->whereNotNull('email_verified_at');
        }

        return $query->pluck('id')->toArray();
    }

    /**
     * Bütün aktiv istifadəçilərin ID-lərini əldə edir
     *
     * @return array İstifadəçi ID-ləri
     */
    public function getAllActiveUserIds(): array
    {
        return User::query()
            ->where('status', 'active')
            ->where('is_system', false)
            ->pluck('id')
            ->toArray();
    }
}
