<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicWorkingHour extends Model
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'clinic_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_closed',
        'note'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'open_time' => 'datetime',
        'close_time' => 'datetime',
        'is_closed' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['day_name', 'time_range'];

    /**
     * Günün adını Azərbaycan dilində qaytarır.
     * @return AttributeAlias
     */
    public function dayName(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return match($this->day_of_week) {
                    'Monday' => 'Bazar ertəsi',
                    'Tuesday' => 'Çərşənbə axşamı',
                    'Wednesday' => 'Çərşənbə',
                    'Thursday' => 'Cümə axşamı',
                    'Friday' => 'Cümə',
                    'Saturday' => 'Şənbə',
                    'Sunday' => 'Bazar',
                    default => $this->day_of_week
                };
            }
        );
    }

    /**
     * Saat aralığını qaytarır.
     * @return AttributeAlias
     */
    public function timeRange(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if ($this->is_closed) {
                    return null;
                }

                return $this->open_time->format('H:i') . ' - ' . $this->close_time->format('H:i');
            }
        );
    }

    /**
     * İş saatına aid klinika əlaqəsi.
     * @return BelongsTo
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
