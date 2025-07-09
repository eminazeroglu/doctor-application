<?php

namespace App\Models;

use App\Traits\Model\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorEducation extends Model
{
    use HasUuid;

    protected $table = 'doctor_education';

    protected $fillable = [
        'uuid',
        'user_id',
        'institution',
        'degree',
        'field_of_study',
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
     * Bu təhsil məlumatının sahibi olan həkim
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Təhsil müddətini hesablayır
     *
     * @return string
     */
    public function getDurationAttribute(): string
    {
        $startYear = $this->start_date->format('Y');

        if ($this->is_current) {
            return $startYear . ' - Davam edir';
        }

        if ($this->end_date) {
            $endYear = $this->end_date->format('Y');
            return $startYear . ' - ' . $endYear;
        }

        return $startYear;
    }
}
