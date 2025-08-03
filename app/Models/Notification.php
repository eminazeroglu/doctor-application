<?php

namespace App\Models;

use App\Enums\NotificationTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends BaseModel
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'type',
        'user_id',
        'title',
        'content',
        'icon',
        'action_url',
        'action_text',
        'data',
        'read_at',
        'send_at',
        'is_sent'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'data' => 'json',
        'read_at' => 'datetime',
        'send_at' => 'datetime',
        'is_sent' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['is_read', 'type_text'];

    /**
     * Bildirişin oxunub-oxunmadığını yoxlayır.
     * @return AttributeAlias
     */
    public function isRead(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->read_at !== null;
            }
        );
    }

    /**
     * Bildiriş növünün mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function typeText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->type ? NotificationTypeEnum::getDescription($this->type) : null;
            }
        );
    }

    /**
     * Bildirişə aid istifadəçi əlaqəsi.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bildirişi oxunmuş kimi işarələyir.
     * @return bool
     */
    public function markAsRead(): bool
    {
        $this->read_at = now();
        return $this->save();
    }

    /**
     * Bildirişi oxunmamış kimi işarələyir.
     * @return bool
     */
    public function markAsUnread(): bool
    {
        $this->read_at = null;
        return $this->save();
    }

    /**
     * Bildirişi göndərilmiş kimi işarələyir.
     * @return bool
     */
    public function markAsSent(): bool
    {
        $this->is_sent = true;
        $this->send_at = now();
        return $this->save();
    }

    /**
     * Oxunmamış bildirişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Oxunmuş bildirişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Göndərilmiş bildirişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('is_sent', true);
    }

    /**
     * Göndərilməmiş bildirişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnsent(Builder $query): Builder
    {
        return $query->where('is_sent', false);
    }

    /**
     * Göndərilməli olan bildirişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('is_sent', false)
            ->where(function($q) {
                $q->whereNull('send_at')
                    ->orWhere('send_at', '<=', now());
            });
    }

    /**
     * Növə görə bildirişləri axtarış.
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Randevularla əlaqəli bildirişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeAppointmentRelated(Builder $query): Builder
    {
        return $query->where('type', 'like', 'appointment%');
    }

    /**
     * Rəylərlə əlaqəli bildirişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeReviewRelated(Builder $query): Builder
    {
        return $query->where('type', 'like', 'review%');
    }

    /**
     * Mesajlarla əlaqəli bildirişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeMessageRelated(Builder $query): Builder
    {
        return $query->where('type', 'like', 'message%');
    }

    /**
     * Sistem bildirişlərini axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('type', 'system');
    }

    /**
     * Ən son bildirişləri axtarış.
     * @param Builder $query
     * @param int $limit
     * @return Builder
     */
    public function scopeLatest(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
