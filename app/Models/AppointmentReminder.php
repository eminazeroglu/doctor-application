<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentReminder extends BaseModel
{

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'appointment_id',
        'type',
        'send_at',
        'is_sent',
        'sent_at'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
        'is_sent' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['type_text', 'is_pending', 'status'];

    /**
     * Xatırlatma növünün mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function typeText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return match($this->type) {
                    'email' => 'E-poçt',
                    'sms' => 'SMS',
                    'app' => 'Tətbiq bildirişi',
                    default => $this->type
                };
            }
        );
    }

    /**
     * Xatırlatmanın gözləmədə olduğunu yoxlayır.
     * @return AttributeAlias
     */
    public function isPending(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return !$this->is_sent && $this->send_at->isFuture();
            }
        );
    }

    /**
     * Xatırlatmanın statusunu qaytarır.
     * @return AttributeAlias
     */
    public function status(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if ($this->is_sent) {
                    return 'Göndərilib';
                }

                if ($this->send_at->isFuture()) {
                    return 'Gözləmədə';
                }

                return 'Ləngidilmiş';
            }
        );
    }

    /**
     * Xatırlatmaya aid randevu əlaqəsi.
     * @return BelongsTo
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Xatırlatmanı göndərilmiş kimi işarələyir.
     * @return bool
     */
    public function markAsSent(): bool
    {
        $this->is_sent = true;
        $this->sent_at = now();
        return $this->save();
    }

    /**
     * Göndərilməmiş xatırlatmaları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnsent(Builder $query): Builder
    {
        return $query->where('is_sent', false);
    }

    /**
     * Göndərilmiş xatırlatmaları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('is_sent', true);
    }

    /**
     * Gözləmədə olan xatırlatmaları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_sent', false)
            ->where('send_at', '>', now());
    }

    /**
     * İndi göndərilməli olan xatırlatmaları qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('is_sent', false)
            ->where('send_at', '<=', now());
    }

    /**
     * E-poçt xatırlatmalarını qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeEmail(Builder $query): Builder
    {
        return $query->where('type', 'email');
    }

    /**
     * SMS xatırlatmalarını qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeSms(Builder $query): Builder
    {
        return $query->where('type', 'sms');
    }

    /**
     * Tətbiq bildirişi xatırlatmalarını qaytarır.
     * @param Builder $query
     * @return Builder
     */
    public function scopeApp(Builder $query): Builder
    {
        return $query->where('type', 'app');
    }
}
