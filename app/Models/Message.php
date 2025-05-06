<?php

namespace App\Models;

use App\Enums\MessageStatusEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'content',
        'attachments',
        'meta_data',
        'status',
        'is_system',
        'is_edited',
        'edited_at',
        'delivered_at',
        'read_at'
    ];

    protected $casts = [
        'attachments' => 'json',
        'meta_data' => 'json',
        'is_system' => 'boolean',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    protected $appends = ['status_text'];

    /* ------------- Əlaqələr ------------- */

    /**
     * Mesajın aid olduğu söhbəti əldə edir
     * */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Mesajı göndərən istifadəçini əldə edir
     * */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /* ------------- Status metodları ------------- */

    /**
     * Mesajı "çatdırıldı" statusuna yeniləyir
     * Yalnız "göndərildi" statusunda olduqda yeniləyir
     * */
    public function markAsDelivered(): void
    {
        if ($this->status === MessageStatusEnum::SENT) {
            $this->update([
                'status' => MessageStatusEnum::DELIVERED,
                'delivered_at' => now()
            ]);
        }
    }

    /**
     * Mesajı "oxundu" statusuna yeniləyir
     * Əgər artıq "oxundu" statusunda deyilsə
     * */
    public function markAsRead(): void
    {
        if ($this->status !== MessageStatusEnum::READ) {
            $this->update([
                'status' => MessageStatusEnum::READ,
                'read_at' => now()
            ]);
        }
    }

    /**
     * Mesajın göstərilən istifadəçidən gəlib-gəlmədiyini yoxlayır
     * */
    public function isFromSender(int $userId): bool
    {
        return $this->sender_id === $userId;
    }

    /* -------------- Virtual atributlar -------------- */

    /**
     * @return Attribute
     */
    public function statusText(): Attribute
    {
        return new Attribute(
            get: fn() => $this->status ? MessageStatusEnum::getDescription($this->status) : null
        );
    }

    /**
     * Mesajın cari istifadəçidən gəlib-gəlmədiyini yoxlayan virtual sahə
     * Bu, frontend-də mesajları sağda/solda göstərmək üçün faydalıdır
     * */
    protected function isFromCurrentUser(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->sender_id === auth()->id()
        );
    }

    /**
     * Mesajın tarixini formatlaşdıran virtual sahə
     * */
    protected function formattedDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at->format('d.m.Y H:i')
        );
    }

}
