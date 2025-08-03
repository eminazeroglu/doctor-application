<?php

namespace App\Models;

use App\Enums\NotificationPreferenceTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'user_id',
        'notification_type',
        'email_enabled',
        'sms_enabled',
        'push_enabled',
        'in_app_enabled'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['type_text'];

    /**
     * Bildiriş növünün mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function typeText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->notification_type ? NotificationPreferenceTypeEnum::getDescription($this->notification_type) : null;
            }
        );
    }

    /**
     * Bildiriş tənzimləməsinə aid istifadəçi əlaqəsi.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Növə görə tənzimləmələri axtarış.
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('notification_type', $type);
    }

    /**
     * E-poçt bildirişləri aktivləşdirilmiş tənzimləmələri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeEmailEnabled(Builder $query): Builder
    {
        return $query->where('email_enabled', true);
    }

    /**
     * SMS bildirişləri aktivləşdirilmiş tənzimləmələri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeSmsEnabled(Builder $query): Builder
    {
        return $query->where('sms_enabled', true);
    }

    /**
     * Push bildirişləri aktivləşdirilmiş tənzimləmələri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopePushEnabled(Builder $query): Builder
    {
        return $query->where('push_enabled', true);
    }

    /**
     * Tətbiq daxili bildirişləri aktivləşdirilmiş tənzimləmələri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeInAppEnabled(Builder $query): Builder
    {
        return $query->where('in_app_enabled', true);
    }
}
