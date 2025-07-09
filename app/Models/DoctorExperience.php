<?php

namespace App\Models;

use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorExperience extends Model
{
    use HasUuid;

    protected $table = 'doctor_experience';

    protected $fillable = [
        'uuid',
        'user_id',
        'institution',
        'position',
        'location',
        'start_date',
        'end_date',
        'is_current',
        'description',
        'custom_fields',
        'order'
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'custom_fields' => 'json'
    ];

    /**
     * Bu təcrübə məlumatının sahibi olan həkim
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * İş müddətini hesablayır
     *
     * @return string
     */
    public function getDurationAttribute(): string
    {
        $startYear = $this->start_date->format('Y');

        if ($this->is_current) {
            return $startYear . ' - Hal-hazırda';
        }

        if ($this->end_date) {
            $endYear = $this->end_date->format('Y');
            return $startYear . ' - ' . $endYear;
        }

        return $startYear;
    }

    /**
     * İş təcrübəsinin illərlə müddətini hesablayır
     *
     * @return int
     */
    public function getYearsOfExperienceAttribute(): int
    {
        $startDate = $this->start_date;
        $endDate = $this->is_current ? now() : ($this->end_date ?: now());

        // İllərlə fərqi hesablayırıq
        return $startDate->diffInYears($endDate);
    }
}
