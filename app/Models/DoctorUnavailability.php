<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorUnavailability extends BaseModel
{
    /**
     * İstifadə ediləcək cədvəl adı.
     * @var string
     */
    protected $table = 'doctor_unavailability';

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'doctor_id',
        'clinic_id',
        'start_datetime',
        'end_datetime',
        'reason',
        'description',
        'is_recurring',
        'recurring_pattern'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'is_recurring' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['datetime_range', 'is_active', 'is_expired', 'duration'];

    /**
     * Tarix və saat aralığını qaytarır.
     * @return AttributeAlias
     */
    public function datetimeRange(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                $start = $this->start_datetime->format('d.m.Y H:i');
                $end = $this->end_datetime->format('d.m.Y H:i');

                return $start . ' - ' . $end;
            }
        );
    }

    /**
     * Məşğulluğun hal-hazırda aktiv olub-olmadığını yoxlayır.
     * @return AttributeAlias
     */
    public function isActive(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return now()->between($this->start_datetime, $this->end_datetime);
            }
        );
    }

    /**
     * Məşğulluq müddətinin bitib-bitmədiyini yoxlayır.
     * @return AttributeAlias
     */
    public function isExpired(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->end_datetime->isPast();
            }
        );
    }

    /**
     * Məşğulluğun müddətini (dəqiqə ilə) qaytarır.
     * @return AttributeAlias
     */
    public function getDurationAttribute(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->start_datetime->diffInMinutes($this->end_datetime);
            }
        );
    }

    /**
     * Məşğulluq qeydinə aid həkim əlaqəsi.
     * @return BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Məşğulluq qeydinə aid klinika əlaqəsi.
     * @return BelongsTo
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
