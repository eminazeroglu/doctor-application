<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorUnavailability extends BaseModel
{
    /**
     * Cədvəlin adı.
     * @var string
     */
    protected $table = 'doctor_unavailability';

    /**
     * Mass-assignable sahələr.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'doctor_id',
        'clinic_id',
        'start_time',   // datetime
        'end_time',     // datetime
        'note',         // nullable string
    ];

    /**
     * Cast-lar.
     * @var array
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    /**
     * Hesablanmış atributlar.
     * @var array
     */
    protected $appends = [
        'time_range',        // "dd.mm.YY HH:ii - dd.mm.YY HH:ii"
        'is_active',         // bool
        'is_expired',        // bool
        'duration_minutes',  // int (dəqiqə)
    ];

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function timeRange(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                $start = $this->start_time?->format('d.m.Y H:i');
                $end   = $this->end_time?->format('d.m.Y H:i');
                return ($start && $end) ? ($start . ' - ' . $end) : null;
            }
        );
    }

    public function isActive(): AttributeAlias
    {
        return new AttributeAlias(
            get: fn () => $this->start_time && $this->end_time && now()->between($this->start_time, $this->end_time)
        );
    }

    public function isExpired(): AttributeAlias
    {
        return new AttributeAlias(
            get: fn () => $this->end_time ? $this->end_time->isPast() : false
        );
    }

    public function durationMinutes(): AttributeAlias
    {
        return new AttributeAlias(
            get: fn () => ($this->start_time && $this->end_time)
                ? $this->start_time->diffInMinutes($this->end_time)
                : 0
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
