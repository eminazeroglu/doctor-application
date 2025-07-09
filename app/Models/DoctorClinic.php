<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorClinic extends Model
{
    protected $fillable = [
        'user_id',
        'clinic_id',
        'is_primary',
        'working_hours',
        'custom_fields'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'working_hours' => 'json',
        'custom_fields' => 'json'
    ];

    /**
     * Bu klinika əlaqəsinin sahibi olan həkim
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Həkimin işlədiyi klinika
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }

    /**
     * İş vaxtının formatlı versiyasını qaytarır
     *
     * @return string|null
     */
    public function getFormattedWorkingHoursAttribute(): ?string
    {
        if (!$this->working_hours) {
            return null;
        }

        $formattedHours = [];

        foreach ($this->working_hours as $day => $hours) {
            if (isset($hours['start']) && isset($hours['end'])) {
                $dayName = $this->getDayName($day);
                $formattedHours[] = "{$dayName}: {$hours['start']} - {$hours['end']}";
            }
        }

        return implode(', ', $formattedHours);
    }

    /**
     * Gün nömrəsinə görə gün adını qaytarır
     *
     * @param int $day
     * @return string
     */
    protected function getDayName(int $day): string
    {
        return match ($day) {
            1 => 'Bazar ertəsi',
            2 => 'Çərşənbə axşamı',
            3 => 'Çərşənbə',
            4 => 'Cümə axşamı',
            5 => 'Cümə',
            6 => 'Şənbə',
            7 => 'Bazar',
            default => 'Bilinmir',
        };
    }
}
