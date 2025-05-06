<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    protected $fillable = [
        'notification_id',
        'channel',
        'success',
        'error',
        'delivered_at'
    ];

    protected $casts = [
        'success' => 'boolean',
        'delivered_at' => 'datetime'
    ];

    /**
     * Notification ilə əlaqə.
     * Hər delivery bir notification-a aiddir.
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Uğurlu göndərmələri filtirləmək üçün scope
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Uğursuz göndərmələri filtirləmək üçün scope
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Müəyyən kanal üzrə göndərmələri filtirləmək üçün scope
     */
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }
}
