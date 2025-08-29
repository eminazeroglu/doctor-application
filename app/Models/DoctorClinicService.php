<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorClinicService extends BaseModel
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'doctor_clinic_id',
        'service_id',
        'price',
        'duration',
        'description',
        'is_active'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'duration' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Xidmətə aid həkim əlaqəsi.
     * @return BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Xidmətə aid klinika əlaqəsi.
     * @return BelongsTo
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Xidmətə aid servis əlaqəsi.
     * @return BelongsTo
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
