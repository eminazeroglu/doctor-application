<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Diagnosis extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'appointment_id',
        'user_id',
        'icd_code',
        'diagnosis_type',
        'name',
        'description',
        'is_chronic',
        'diagnosed_at',
        'custom_fields'
    ];

    protected $casts = [
        'is_chronic' => 'boolean',
        'diagnosed_at' => 'date',
        'custom_fields' => 'json'
    ];

    /**
     * Bu diaqnoza aid randevu
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Diaqnozu alan pasiyent
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Diaqnozu qoyan həkim
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Bu diaqnoza aid reseptlər
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Bu diaqnoza aid müalicə planları
     */
    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }
}
