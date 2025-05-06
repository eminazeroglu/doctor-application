<?php

namespace App\Models;

use App\Enums\CredentialTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

class BlockedCredential extends BaseModel
{
    protected $fillable = [
        'type',
        'value',
        'reason',
        'blocked_until',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = ['type_text', 'remaining_time'];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Bloku yaradan istifadəçi ilə əlaqə
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Bloku yeniləyən istifadəçi ilə əlaqə
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Email bloklarını filtirləmək üçün scope
     */
    public function scopeEmails($query)
    {
        return $query->where('type', CredentialTypeEnum::Email);
    }

    /**
     * Telefon bloklarını filtirləmək üçün scope
     */
    public function scopePhones($query)
    {
        return $query->where('type', CredentialTypeEnum::Phone);
    }

    /**
     * IP bloklarını filtirləmək üçün scope
     */
    public function scopeIps($query)
    {
        return $query->where('type', CredentialTypeEnum::IP);
    }

    /**
     * Müddəti bitməmiş blokları filtirləmək üçün scope
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('blocked_until') // Daimi bloklar
            ->orWhere('blocked_until', '>', now()); // Müddəti bitməmiş bloklar
        });
    }

    /**
     * Müddəti bitmiş blokları filtirləmək üçün scope
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('blocked_until')
            ->where('blocked_until', '<=', now());
    }

    /**
     * Daimi blokları filtirləmək üçün scope
     */
    public function scopePermanent($query)
    {
        return $query->whereNull('blocked_until');
    }

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    /**
     * Blokun qalan müddətini formatlaşdırılmış şəkildə qaytarır
     */
    protected function remainingTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->isPermanent()) {
                    return t('enums.credential_remaining.permanent');
                }

                if ($this->isExpired()) {
                    return t('enums.credential_remaining.expired');
                }

                return $this->blocked_until->diffForHumans();
            }
        );
    }

    protected function typeText(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->type ? CredentialTypeEnum::getDescription($this->type) : '-';
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Blokun daimi olub-olmadığını yoxlayır
     */
    public function isPermanent(): bool
    {
        return $this->blocked_until === null;
    }

    /**
     * Blokun müddətinin bitib-bitmədiyini yoxlayır
     */
    public function isExpired(): bool
    {
        if ($this->isPermanent()) {
            return false;
        }

        return $this->blocked_until->isPast();
    }

    /**
     * Blokun aktiv olub-olmadığını yoxlayır (həm is_active, həm də müddət)
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return $this->isPermanent() || !$this->isExpired();
    }

    /**
     * Bloku deaktiv edir
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Blok müddətini uzadır
     */
    public function extend(Carbon $newDate): bool
    {
        if ($this->isPermanent()) {
            return false;
        }

        return $this->update(['blocked_until' => $newDate]);
    }

    /**
     * Bloku daimi hala çevirir
     */
    public function makePermanent(): bool
    {
        return $this->update(['blocked_until' => null]);
    }
}
