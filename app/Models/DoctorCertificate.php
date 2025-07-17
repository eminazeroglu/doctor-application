<?php

namespace App\Models;

use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorCertificate extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'issuer',
        'issue_date',
        'expiry_date',
        'file_path',
        'is_verified',
        'verified_at',
        'description',
        'custom_fields',
        'order'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
        'custom_fields' => 'json'
    ];

    /**
     * Bu sertifikatın sahibi olan həkim
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Sertifikatın bitib-bitmədiyini yoxlayır
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    /**
     * Sertifikat faylının URL-ni qaytarır
     *
     * @return string|null
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return asset('storage/' . $this->file_path);
    }
}
