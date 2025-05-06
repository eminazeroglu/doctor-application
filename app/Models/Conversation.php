<?php

namespace App\Models;

use App\Enums\ConversationStatusEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'status',
        'conversationable_type',
        'conversationable_id',
        'creator_id',
        'receiver_id',
        'meta_data',
        'last_activity_at',
        'is_pinned',
        'archived_at'
    ];

    protected $casts = [
        'meta_data' => 'json',
        'last_activity_at' => 'datetime',
        'archived_at' => 'datetime',
        'is_pinned' => 'boolean'
    ];

    protected $appends = ['status_text'];

    /*
     * ----------- Əlaqələr -----------
     * */

    /**
     * Söhbətə aid bütün mesajları əldə edir - yaranma tarixinə görə sıralanır
     * */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Söhbəti başladan istifadəçini əldə edir
     * */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Söhbətin qarşı tərəfini (alıcısını) əldə edir
     * */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Söhbətin əlaqəli olduğu obyekti (məsələn, elan) əldə edir
     * */
    public function conversationable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Söhbətdəki ən son mesajı əldə edir
     * */
    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    /* ------------- Köməkçi metodlar ------------- */

    /**
     * @return Attribute
     */
    public function statusText(): Attribute
    {
        return new Attribute(
            get: fn() => $this->status ? ConversationStatusEnum::getDescription($this->status) : null
        );
    }

    /**
     * Söhbəti arxivləşdirir - statusunu dəyişir və arxivləşmə tarixini qeyd edir
     * */
    public function markAsArchived(): void
    {
        $this->update([
            'status' => ConversationStatusEnum::ARCHIVED,
            'archived_at' => now()
        ]);
    }

    /**
     * Söhbəti aktiv edir - statusunu dəyişir və arxivləşmə tarixini təmizləyir
     * */
    public function markAsActive(): void
    {
        $this->update([
            'status' => ConversationStatusEnum::ACTIVE,
            'archived_at' => null
        ]);
    }

    /**
     * Söhbətin sabitlənmiş (pinned) vəziyyətini dəyişdirir
     * Sabitlənmiş söhbətlər siyahıda üstdə göstərilir
     * */
    public function togglePin(): void
    {
        $this->update([
            'is_pinned' => !$this->is_pinned
        ]);
    }

    /**
     * Söhbətin son aktivlik tarixini yeniləyir
     * Bu, siyahıda söhbətləri son aktivliyə görə sıralamaq üçün istifadə olunur
     * */
    public function updateLastActivity(): void
    {
        $this->update([
            'last_activity_at' => now()
        ]);
    }

    /**
     * Söhbətin meta məlumatlarını yeniləyir - JSON sahəsini birləşdirmə ilə
     * Meta məlumatlar son mesaj məzmunu, oxunmamış mesaj sayı və s. saxlaya bilər
     * */
    public function updateMetaData(array $data): void
    {
        $metaData = $this->meta_data ?? [];
        $this->update([
            'meta_data' => array_merge($metaData, $data)
        ]);
    }

    /* ------------- Yardımçı düzəliş metodları ------------- */

    /**
     * İstifadəçiyə görə söhbət iştirakçısını qaytarır
     * opposite=true olduqda qarşı tərəfi qaytarır
     * */
    public function getParticipant(bool $opposite = false): User
    {
        $currentUserId = auth()->id();

        if ($opposite) {
            return $currentUserId === $this->creator_id
                ? $this->receiver
                : $this->creator;
        }

        return $currentUserId === $this->creator_id
            ? $this->creator
            : $this->receiver;
    }

    /**
     * İstifadəçi üçün oxunmamış mesaj sayını qaytarır
     * */
    public function getUnreadCount(int $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
