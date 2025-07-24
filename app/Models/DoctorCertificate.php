<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorCertificate extends BaseModel
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'doctor_id',
        'name',
        'issuing_organization',
        'issue_date',
        'expiry_date',
        'description',
        'document_path',
        'is_verified'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_verified' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['is_expired', 'document_url'];

    /**
     * Sertifikatın müddətinin bitib-bitmədiyini yoxlayır.
     * @return AttributeAlias
     */
    public function isExpired(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->expiry_date) {
                    return false;
                }

                return $this->expiry_date->isPast();
            }
        );
    }

    /**
     * Sənəd URL-i qaytarır.
     * @return AttributeAlias
     */
    public function documentUrl(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->document_path ? asset('storage/' . $this->document_path) : null;
            }
        );
    }

    /**
     * Sertifikata aid həkim əlaqəsi.
     * @return BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
