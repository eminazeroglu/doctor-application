<?php

namespace App\Models;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends BaseModel
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'channel',
        'recipient',
        'notification_type',
        'template_code',
        'subject',
        'content',
        'is_successful',
        'error_message',
        'sent_at'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'is_successful' => 'boolean',
        'sent_at' => 'datetime',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['channel_text', 'status', 'notification_type_text'];

    /**
     * Bildiriş kanalının mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function channelText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->channel ? NotificationChannelEnum::getDescription($this->channel) : null;
            }
        );
    }

    /**
     * Bildiriş statusunu qaytarır.
     * @return AttributeAlias
     */
    public function status(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->is_successful ? 'Uğurlu' : 'Uğursuz';
            }
        );
    }

    /**
     * Bildiriş növünün mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function notificationTypeText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->notification_type ? NotificationTypeEnum::getDescription($this->notification_type) : null;
            }
        );
    }

    /**
     * Bildiriş loguna aid istifadəçi əlaqəsi.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Uğurla göndərilmiş bildiriş loglarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('is_successful', true);
    }

    /**
     * Uğursuz göndərilmiş bildiriş loglarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('is_successful', false);
    }

    /**
     * Kanala görə bildiriş loglarını axtarış.
     * @param Builder $query
     * @param string $channel
     * @return Builder
     */
    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    /**
     * Bildiriş növünə görə bildiriş loglarını axtarış.
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Şablon koduna görə bildiriş loglarını axtarış.
     * @param Builder $query
     * @param string $templateCode
     * @return Builder
     */
    public function scopeWithTemplateCode(Builder $query, string $templateCode): Builder
    {
        return $query->where('template_code', $templateCode);
    }

    /**
     * Alıcıya görə bildiriş loglarını axtarış.
     * @param Builder $query
     * @param string $recipient
     * @return Builder
     */
    public function scopeToRecipient(Builder $query, string $recipient): Builder
    {
        return $query->where('recipient', 'like', "%{$recipient}%");
    }

    /**
     * Göndərilmə tarixinə görə bildiriş loglarını axtarış.
     * @param Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return Builder
     */
    public function scopeSentBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('sent_at', [$startDate, $endDate]);
    }

    /**
     * Son dövrün bildiriş loglarını axtarış.
     * @param Builder $query
     * @param int $days
     * @return Builder
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('sent_at', '>=', now()->subDays($days));
    }

    /**
     * E-poçt bildiriş loglarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeEmail(Builder $query): Builder
    {
        return $query->where('channel', 'email');
    }

    /**
     * SMS bildiriş loglarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeSms(Builder $query): Builder
    {
        return $query->where('channel', 'sms');
    }

    /**
     * Push bildiriş loglarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopePush(Builder $query): Builder
    {
        return $query->where('channel', 'push');
    }

    /**
     * Tətbiq daxili bildiriş loglarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeInApp(Builder $query): Builder
    {
        return $query->where('channel', 'in_app');
    }
}
