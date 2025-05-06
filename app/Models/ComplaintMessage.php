<?php

namespace App\Models;

use App\Enums\ComplaintMessageStatusEnum;
use App\Traits\Model\HasLoggable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintMessage extends BaseModel
{
    use HasLoggable;

    /**
     * Doldurula bilən sahələr
     */
    protected $fillable = [
        'complaint_id',    // Aid olduğu şikayət
        'user_id',        // Mesajı yazan istifadəçi
        'message',        // Mesajın mətni
        'attachments',    // Əlavə fayllar (şəkillər və s.)
        'status',         // Mesajın statusu
        'is_staff_reply'  // Staff/Admin tərəfindən yazılıb?
    ];

    /**
     * Cast ediləcək sahələr
     */
    protected $casts = [
        'attachments' => 'json',
        'is_staff_reply' => 'boolean'
    ];

    /**
     * Virtual atributlar
     */
    protected $appends = ['status_text'];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - ƏLAQƏLƏR
    |--------------------------------------------------------------------------
    */

    /**
     * Aid olduğu şikayətlə əlaqə
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * Mesajı yazan istifadəçi ilə əlaqə
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES - XÜSUSİ ATRİBUTLAR
    |--------------------------------------------------------------------------
    */

    /**
     * Statusun mətn təsviri
     */
    protected function statusText(): Attribute
    {
        return new Attribute(
            get: fn() => $this->status ? ComplaintMessageStatusEnum::getDescription($this->status) : null
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES - FİLTR METODLARI
    |--------------------------------------------------------------------------
    */

    /**
     * Statuslara görə filtrlər
     */
    public function scopePending($query)
    {
        return $query->where('status', ComplaintMessageStatusEnum::Pending);
    }

    public function scopeRead($query)
    {
        return $query->where('status', ComplaintMessageStatusEnum::Read);
    }

    public function scopeHidden($query)
    {
        return $query->where('status', ComplaintMessageStatusEnum::Hidden);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS - KÖMƏKÇİ METODLAR
    |--------------------------------------------------------------------------
    */

    /**
     * Mesajın oxunub-oxunmadığını yoxlayır
     */
    public function isRead(): bool
    {
        return $this->status === ComplaintMessageStatusEnum::Read;
    }

    /**
     * Mesajın staff/admin tərəfindən yazılıb-yazılmadığını yoxlayır
     */
    public function isStaffReply(): bool
    {
        return $this->is_staff_reply;
    }

    /**
     * Mesajı oxunmuş kimi işarələyir
     */
    public function markAsRead(): void
    {
        if ($this->status === ComplaintMessageStatusEnum::Pending) {
            $this->update([
                'status' => ComplaintMessageStatusEnum::Read
            ]);
        }
    }
}
