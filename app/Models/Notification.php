<?php

namespace App\Models;

use App\Enums\NotificationPriorityEnum;
use App\Enums\NotificationTypeEnum;
use App\Traits\Model\HasLoggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends BaseModel
{
    use HasLoggable;

    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
        'send_at',
        'priority'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'send_at' => 'datetime'
    ];

    protected $appends = ['type_text', 'priority_text'];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('send_at')->where('send_at', '>', now());
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('send_at')->orWhere('send_at', '<=', now());
    }

    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->where('priority', NotificationPriorityEnum::HIGH);
    }

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES
    |--------------------------------------------------------------------------
    */
    protected function typeText(): Attribute
    {
        return new Attribute(
            get: fn() => $this->type ? NotificationTypeEnum::getDescription($this->type) : ''
        );
    }

    protected function priorityText(): Attribute
    {
        return new Attribute(
            get: fn() => $this->priority ? NotificationPriorityEnum::getDescription($this->priority) : ''
        );
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */
    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
    }

    public function markAsUnread(): void
    {
        if (!is_null($this->read_at)) {
            $this->forceFill(['read_at' => null])->save();
        }
    }

    public function schedule(string $datetime): void
    {
        $this->update(['send_at' => $datetime]);
    }

    public function cancel(): void
    {
        $this->update(['send_at' => null]);
    }

    /**
     * Notification-ın uğurla göndərilib-göndərilmədiyini yoxlamaq üçün
     */
    public function isDelivered(): bool
    {
        return $this->deliveries()->successful()->exists();
    }

    /**
     * Notification-ın hansı kanallarla göndərildiyini qaytarır
     */
    public function getDeliveryChannels(): array
    {
        return $this->deliveries()
            ->successful()
            ->pluck('channel')
            ->toArray();
    }

}
